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
use JWeiland\Bynder2\Repository\SysFileRepository;
use JWeiland\Bynder2\Service\BynderClientFactory;
use JWeiland\Bynder2\Service\BynderService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
        if ($this->checkEnvironment($output) === false) {
            return Command::FAILURE;
        }

        $this->synchronizeStorages($output);

        $this->logger->info('All files of all bynder storages have been synchronized');

        return 0;
    }

    private function synchronizeStorages(OutputInterface $output): void
    {
        foreach ($this->bynderStorages as $storage) {
            $this->logger->info('Start synchronizing files of storage with UID: ' . $storage->getUid());
            if (($client = $this->createBynderClient($storage, $output)) === null) {
                continue;
            }

            $allExistingIdentifiersOfStorage = $this->sysFileRepository->getFileIdentifiersOfStorage($storage->getUid());
            $allNewIdentifiersOfStorage = [];
            $synchronizedFiles = 0;
            $indexer = $this->getIndexer($storage);
            foreach ($this->bynderService->getFiles($client, 0, 0) as $fileInformationFromResponse) {
                $this->synchronizeFile($fileInformationFromResponse, $storage, $indexer, $output);
                $allNewIdentifiersOfStorage[] = $fileInformationFromResponse['id'];
                $synchronizedFiles++;
            }

            $output->writeln(sprintf(
                'We have synchronized %d and deleted %d files.',
                $synchronizedFiles,
                $this->deleteMissingFiles(
                    $allExistingIdentifiersOfStorage,
                    $allNewIdentifiersOfStorage,
                    $storage,
                    $output
                ),
            ));
            $this->logger->info('Finished synchronizing files of storage with UID: ' . $storage->getUid());
        }
    }

    private function synchronizeFile(
        array $fileInformationFromResponse,
        ResourceStorage $storage,
        Indexer $indexer,
        OutputInterface $output,
    ): void {
        if ($output->isVeryVerbose()) {
            $output->writeln(sprintf('Synchronizing file with ID: %s', $fileInformationFromResponse['id']));
        }

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
                if ($output->isVerbose()) {
                    $output->writeln(sprintf('File with ID: %s was updated.', $fileInformationFromResponse['id']));
                }
            }
        } else {
            $indexer->createIndexEntry($fileInformationFromResponse['id']);
            if ($output->isVerbose()) {
                $output->writeln(sprintf('File with ID: %s was created.', $fileInformationFromResponse['id']));
            }
        }

        $this->fileInfoCache->remove($fileInformationFromResponse['id']);

        if ($output->isVeryVerbose()) {
            $output->writeln(sprintf('Finished synchronizing file with ID: %s', $fileInformationFromResponse['id']));
        }
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
        OutputInterface $output
    ): int {
        $deletedFiles = 0;
        $missingIdentifiers = array_diff($existingIdentifiers, $newIdentifiers);
        foreach ($missingIdentifiers as $missingIdentifier) {
            $this->sysFileRepository->deleteFile($storage->getUid(), $missingIdentifier);
            if ($output->isVerbose()) {
                $output->writeln(sprintf('File with ID: %s was deleted.', $missingIdentifier));
            }
            $deletedFiles++;
        }

        return $deletedFiles;
    }

    private function createBynderClient(ResourceStorage $storage, OutputInterface $output): ?BynderClient
    {
        try {
            return $this->bynderClientFactory->createClient($storage->getConfiguration());
        } catch (\Exception $e) {
            $output->writeln('Could not create Bynder client because of invalid configuration');
            $this->logger->error('Could not create Bynder client because of invalid configuration', ['exception' => $e]);
        }

        return null;
    }

    private function checkEnvironment(OutputInterface $output): bool
    {
        $tableColumns = $this->getColumnsOfSysFileMetadata();

        foreach (self::REQUIRED_COLUMNS as $column) {
            if (!isset($tableColumns[$column])) {
                $output->writeln(
                    'Missing columns detected in the "sys_file_metadata" table. '
                    . 'Please analyze the database using the Install Tool before proceeding.'
                );
                $this->logger->warning('Missing columns in sys_file_metadata. Stopped synchronization.');
                return false;
            }
        }

        if ($this->bynderStorages->count() === 0) {
            $output->writeln('No bynder storages found.');
            $this->logger->warning('No bynder storages found.');
            return false;
        }

        foreach ($this->bynderStorages as $storage) {
            if (!$storage->autoExtractMetadataEnabled()) {
                $output->writeln('Auto extract metadata is disabled for storage with UID: ' . $storage->getUid());
                $this->logger->warning('Auto extract metadata is disabled for storage with UID: ' . $storage->getUid());
                return false;
            }
        }

        return true;
    }

    private function getColumnsOfSysFileMetadata(): array
    {
        try {
            return GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('sys_file_metadata')
                ->createSchemaManager()
                ->listTableColumns('sys_file_metadata');
        } catch (Exception) {
        }

        return [];
    }

    private function getIndexer(ResourceStorage $resourceStorage): Indexer
    {
        return GeneralUtility::makeInstance(Indexer::class, $resourceStorage);
    }
}
