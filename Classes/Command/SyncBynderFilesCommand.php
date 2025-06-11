<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Command;

use Bynder\Api\BynderClient;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\BigIntType;
use JWeiland\Bynder2\Repository\SysFileRepository;
use JWeiland\Bynder2\Service\BynderClientFactory;
use JWeiland\Bynder2\Service\BynderService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Bynder does not support folders; therefore, all files are located at the root level. This makes it challenging to
 * identify related files, especially since the file list module currently lacks filtering options. The only available
 * method is search functionality.
 *
 * Since FAL search operates on the sys_file and sys_file_metadata tables, a command is required to synchronize all
 * files from Bynder into these tables.
 */
class SyncBynderFilesCommand extends Command
{
    private const REQUIRED_COLUMNS = [
        'bynder2_thumb_mini',
        'bynder2_thumb_thul',
        'bynder2_thumb_webimage',
    ];

    /**
     * @param \SplObjectStorage<ResourceStorage> $bynderStorages
     */
    public function __construct(
        private readonly \SplObjectStorage $bynderStorages,
        private readonly BynderService $bynderService,
        private readonly BynderClientFactory $bynderClientFactory,
        private readonly SysFileRepository $sysFileRepository,
        private readonly ResourceFactory $resourceFactory,
        private readonly FrontendInterface $fileInfoCache,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription(
            'Executing this command will retrieve all file records via Bynder API and creates/updates ' .
            'the related sys_file/sys_file_metadata records of TYPO3s FAL system.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $timer = time();

        $io = new SymfonyStyle($input, $output);
        $io->title('Start synchronizing files from all registered bynder storages to TYPO3');

        if ($this->checkEnvironment($io) === false) {
            return Command::FAILURE;
        }

        foreach ($this->bynderStorages as $storage) {
            $io->section('Start synchronizing files of storage with UID: ' . $storage->getUid());
            if (($client = $this->createBynderClient($storage, $io)) === null) {
                continue;
            }
            $this->synchronizeStorage($storage, $client, $io);
        }

        $this->logger->info('All files of all bynder storages have been synchronized');
        $io->success(sprintf(
            'All files of all bynder storages have been synchronized in %s',
            Helper::formatTime(time() - $timer)
        ));

        return Command::SUCCESS;
    }

    private function synchronizeStorage(ResourceStorage $storage, BynderClient $client, SymfonyStyle $io): void
    {
        $existingIdentifiers = $this->sysFileRepository->getFileIdentifiersOfStorage($storage->getUid());
        $newIdentifiers = [];

        $synchronizedFiles = 0;
        $indexer = $this->getIndexer($storage);
        foreach ($this->bynderService->getFiles($client, 0, 0) as $fileInformationFromResponse) {
            $this->synchronizeFile($fileInformationFromResponse, $storage, $indexer, $io);
            $newIdentifiers[] = $fileInformationFromResponse['id'];
            $synchronizedFiles++;
        }

        $io->text(sprintf(
            'We have synchronized %d and deleted %d files.',
            $synchronizedFiles,
            $this->deleteMissingFiles($existingIdentifiers, $newIdentifiers, $storage, $io),
        ));
    }

    private function synchronizeFile(
        array $fileInformationFromResponse,
        ResourceStorage $storage,
        Indexer $indexer,
        SymfonyStyle $io
    ): void {
        // Only the file ID is used, but all Bynder file data is kept in a transient cache
        // for full driver access later.
        $this->fileInfoCache->set($fileInformationFromResponse['id'], $fileInformationFromResponse);

        if ($storage->hasFile($fileInformationFromResponse['id'])) {
            $fileObject = $this->resourceFactory->getFileObjectByStorageAndIdentifier(
                $storage->getUid(),
                $fileInformationFromResponse['id']
            );

            if ($fileObject instanceof FileInterface) {
                $indexer->updateIndexEntry($fileObject);
                $io->text(sprintf('%-35s    %s', $fileInformationFromResponse['id'], 'Updated'));
            } else {
                $io->text(sprintf('%-35s    %s', $fileInformationFromResponse['id'], 'Error. See logs.'));
                $this->logger->error(sprintf(
                    'File %s is not an interface of TYPO3\'s FileInterface.',
                    $fileInformationFromResponse['id']
                ));
            }
        } else {
            $indexer->createIndexEntry($fileInformationFromResponse['id']);
            $io->text(sprintf('%-35s    %s', $fileInformationFromResponse['id'], 'Created'));
        }

        $this->fileInfoCache->remove($fileInformationFromResponse['id']);
    }

    /**
     * Bynder does not provide information about deleted files, only the current state.
     * Therefore, we calculate the difference between all existing and incoming records and remove any entries
     * that are no longer present.
     */
    private function deleteMissingFiles(
        array $existingIdentifiers,
        array $newIdentifiers,
        ResourceStorage $storage,
        SymfonyStyle $io
    ): int {
        $deletedFiles = 0;
        $missingIdentifiers = array_diff($existingIdentifiers, $newIdentifiers);
        foreach ($missingIdentifiers as $missingIdentifier) {
            $this->sysFileRepository->deleteFile($storage->getUid(), $missingIdentifier);
            $io->text(sprintf('%-35s    %s', $missingIdentifier, 'Deleted'));
            $deletedFiles++;
        }

        return $deletedFiles;
    }

    private function createBynderClient(ResourceStorage $storage, SymfonyStyle $io): ?BynderClient
    {
        try {
            return $this->bynderClientFactory->createClient($storage->getConfiguration());
        } catch (\Exception $e) {
            $io->writeln('Could not create Bynder client because of invalid configuration');
            $this->logger->error('Could not create Bynder client because of invalid configuration', ['exception' => $e]);
        }

        return null;
    }

    private function checkEnvironment(SymfonyStyle $io): bool
    {
        $sysFileMetadataColumns = $this->getColumnsOfTable('sys_file_metadata');

        foreach (self::REQUIRED_COLUMNS as $column) {
            if (!array_key_exists($column, $sysFileMetadataColumns)) {
                $io->text(
                    'Missing columns detected in the "sys_file_metadata" table. '
                    . 'Please analyze the database using the Install Tool before proceeding.'
                );
                $this->logger->warning('Missing columns in sys_file_metadata. Stopped synchronization.');
                return false;
            }
        }

        $sysFileColumns = $this->getColumnsOfTable('sys_file');
        if (!$sysFileColumns['size']->getType() instanceof BigIntType) {
            $io->writeln(
                'Column "size" in the "sys_file" table is not of type BIGINT. '
                . 'Please analyze the database using the Install Tool before proceeding.'
            );
            return false;
        }

        if ($this->bynderStorages->count() === 0) {
            $io->writeln('No bynder storages found.');
            $this->logger->warning('No bynder storages found.');
            return false;
        }

        foreach ($this->bynderStorages as $storage) {
            if (!$storage->autoExtractMetadataEnabled()) {
                $io->writeln('Auto extract metadata is disabled for storage with UID: ' . $storage->getUid());
                $this->logger->warning('Auto extract metadata is disabled for storage with UID: ' . $storage->getUid());
                return false;
            }
        }

        return true;
    }

    /**
     * @return Column[]
     */
    private function getColumnsOfTable(string $table): array
    {
        try {
            return GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable($table)
                ->createSchemaManager()
                ->listTableColumns($table);
        } catch (Exception) {
        }

        return [];
    }

    private function getIndexer(ResourceStorage $resourceStorage): Indexer
    {
        return GeneralUtility::makeInstance(Indexer::class, $resourceStorage);
    }
}
