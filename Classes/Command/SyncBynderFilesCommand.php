<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Command;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * Bynder does not work with folders, so all files are on root level. So, it's hard to find the related files
 * as we currently do not have any filtering options in filelist module. The only option you have is: searching.
 * As FAL search is realized on sys_file and sys_file_metadata tables we need a command to sync all files from
 * Bynder into these tables.
 */
class SyncBynderFilesCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var \SplObjectStorage|ResourceStorage[]
     */
    protected $bynderStorages;

    /**
     * @var FrontendInterface
     */
    protected $fileInfoCache;

    /**
     * @var FrontendInterface
     */
    protected $pageNavCache;

    /**
     * Instead of overwriting the constructor of parent Command class
     * we inject Bynder Storages with DI
     */
    public function setBynderStorages(\SplObjectStorage $bynderStorages): void
    {
        $this->bynderStorages = $bynderStorages;
    }

    /**
     * Instead of overwriting the constructor of parent Command class
     * we inject FileInfo Cache with DI
     */
    public function setFileInfoCache(FrontendInterface $fileInfoCache): void
    {
        $this->fileInfoCache = $fileInfoCache;
    }

    /**
     * Instead of overwriting the constructor of parent Command class
     * we inject PageNav Cache with DI
     */
    public function setPageNavCache(FrontendInterface $pageNavCache): void
    {
        $this->pageNavCache = $pageNavCache;
    }

    protected function configure(): void
    {
        $this->setDescription(
            'Executing this command will retrieve all file records via Bynder API and creates/updates ' .
            'the related sys_file/sys_file_metadata records of TYPO3s FAL system.'
        );
    }

    /**
     * Start synchronization of bynder files
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        $output->writeln('Clear bynder caches');
        $this->fileInfoCache->flush();
        $this->logger->info('Bynder FileInfo cache has been flushed');
        $this->pageNavCache->flush();
        $this->logger->info('Bynder PageNav cache has been flushed');

        $output->writeln('Start synchronizing bynder files');
        $this->synchronizeStorages();
        $this->logger->info('All Bynder files have been synchronized');

        return 0;
    }

    protected function synchronizeStorages(): void
    {
        if ($this->bynderStorages === []) {
            $this->output->writeln('No bynder storages found.');
            $this->logger->warning('No bynder storages found.');
            return;
        }

        foreach ($this->bynderStorages as $bynderStorage) {
            $this->logger->info('Start synchronizing files of storage with UID: ' . $bynderStorage->getUid());
            $this->logger->debug('If solr or other file extractors are activated indexing will need its time.');
            $this->synchronizeStorage($bynderStorage);
            $this->logger->info('Finished synchronizing files of storage with UID: ' . $bynderStorage->getUid());
        }
    }

    protected function synchronizeStorage(ResourceStorage $bynderStorage): void
    {
        // This will retrieve ALL files from Bynder API.
        // The fileInfo caches will be updated,
        // detect changes in storage,
        // check and mark missing files
        try {
            $this->getIndexer($bynderStorage)->processChangesInStorages();
        } catch (\Exception $e) {
            $this->logger->error('TYPO3 indexer error: ' . $e->getMessage());
            return;
        }

        $this->output->writeln(
            sprintf(
                'Synchronized %d files in bynder storage %d',
                $this->countFilesInStorage($bynderStorage),
                $bynderStorage->getUid()
            )
        );
    }

    protected function countFilesInStorage(ResourceStorage $storage): int
    {
        try {
            return $storage->countFilesInFolder($storage->getRootLevelFolder());
        } catch (InsufficientFolderAccessPermissionsException $insufficientFolderAccessPermissionsException) {
            $this->output->writeln('CLI user does not have permission to count files of bynder storage');
            $this->logger->error('CLI user does not have permission to count files of bynder storage');
        }

        return 0;
    }

    protected function getIndexer(ResourceStorage $storage): Indexer
    {
        return GeneralUtility::makeInstance(Indexer::class, $storage);
    }
}
