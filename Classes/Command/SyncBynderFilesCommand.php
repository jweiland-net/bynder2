<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Command;

use Doctrine\DBAL\Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Bynder does not work with folders, so all files are on the root level. So, it's hard to find the related files
 * as we currently do not have any filtering options in the filelist module. The only option you have is: searching.
 * As FAL search is realized on sys_file and sys_file_metadata tables, we need a command to sync all files from
 * Bynder into these tables.
 */
class SyncBynderFilesCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly \SplObjectStorage $bynderStorages,
        private readonly FrontendInterface $fileInfoCache,
        private readonly FrontendInterface $pageNavCache,
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

    /**
     * Start synchronization of bynder files
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Start synchronizing bynder files');
        $this->synchronizeStorages($output);
        $this->logger->info('All Bynder files have been synchronized');

        return 0;
    }

    protected function synchronizeStorages(OutputInterface $output): void
    {
        if ($this->bynderStorages->count() === 0) {
            $output->writeln('No bynder storages found.');
            $this->logger->warning('No bynder storages found.');
            return;
        }

        foreach ($this->bynderStorages as $bynderStorage) {
            $this->logger->info('Start synchronizing files of storage with UID: ' . $bynderStorage->getUid());
            $this->logger->debug('If solr or other file extractors are activated indexing will need its time.');
            $this->synchronizeStorage($bynderStorage, $output);
            $this->logger->info('Finished synchronizing files of storage with UID: ' . $bynderStorage->getUid());
        }
    }

    /**
     * This will retrieve ALL files from Bynder API.
     * The fileInfo and pageNav caches will be updated,
     * Detect changes in storage,
     * Check and mark missing files
     */
    protected function synchronizeStorage(ResourceStorage $bynderStorage, OutputInterface $output): void
    {
        try {
            $this->getIndexer($bynderStorage)->processChangesInStorages();
        } catch (\Exception $e) {
            $this->logger->error('TYPO3 indexer error: ' . $e->getMessage(), ['exception' => $e]);
            return;
        }

        $this->removeOldCacheEntries($this->fileInfoCache);
        $this->removeOldCacheEntries($this->pageNavCache);

        $output->writeln(
            sprintf(
                'Synchronized %d files in bynder storage %d',
                $this->countFilesInStorage($bynderStorage, $output),
                $bynderStorage->getUid()
            )
        );
    }

    protected function countFilesInStorage(ResourceStorage $storage, OutputInterface $output): int
    {
        try {
            return $storage->countFilesInFolder($storage->getRootLevelFolder());
        } catch (InsufficientFolderAccessPermissionsException $insufficientFolderAccessPermissionsException) {
            $output->writeln('CLI user does not have permission to count files of bynder storage');
            $this->logger->error(
                'CLI user does not have permission to count files of bynder storage',
                [
                    'exception' => $insufficientFolderAccessPermissionsException,
                ]
            );
        }

        return 0;
    }

    protected function removeOldCacheEntries(FrontendInterface $cache): void
    {
        $cacheTags = $this->getSortedCacheTags($cache);
        if ($cacheTags === []) {
            $this->logger->info(
                'Cache is no TYPO3 DB Cache Backend. To update your cache entries you have to clean up the cache entries manually.'
            );
            return;
        }

        // Remove the last entry, as we don't want to remove our just freshly created cache entries
        array_pop($cacheTags);

        $cache->flushByTags($cacheTags);
    }

    protected function getSortedCacheTags(FrontendInterface $cache): array
    {
        $cacheTable = $this->getCacheTagTable($cache);
        if ($cacheTable === '') {
            return [];
        }

        $connection = $this->getConnectionPool()->getConnectionForTable($cacheTable);
        $queryResult = $connection->select(
            ['tag'],
            $cacheTable,
            [],
            [],
            ['tag' => 'ASC'],
        );

        try {
            return $queryResult->fetchAllAssociative();
        } catch (Exception $e) {
        }

        return [];
    }

    protected function getCacheTagTable(FrontendInterface $cache): string
    {
        $cacheBackend = $cache->getBackend();

        return $cacheBackend instanceof Typo3DatabaseBackend ? $cacheBackend->getTagsTable() : '';
    }

    private function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }

    protected function getIndexer(ResourceStorage $storage): Indexer
    {
        return GeneralUtility::makeInstance(Indexer::class, $storage);
    }
}
