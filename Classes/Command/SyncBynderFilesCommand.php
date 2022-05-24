<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/fal-bynder.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\FalBynder\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * Bynder does not work with folders, so all files are on root level. So, it's hard to find the related files
 * as we currently do not have any filtering options in filelist module. The only option you have is: searching.
 * As FAL search is realized on sys_file and sys_file_metadata tables we need a command to sync all files from
 * Bynder into these tables.
 */
class SyncBynderFilesCommand extends Command
{
    /**
     * @var OutputInterface
     */
    protected $output;

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

        $output->writeln('Clear bynder information cache');
        $this->clearCache();
        $output->writeln('Start synchronizing bynder files');
        $this->synchronizeStorages();

        return 0;
    }

    protected function clearCache(): void
    {
        try {
            $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('fal_bynder');
            $cache->flush();
        } catch (NoSuchCacheException $noSuchCacheException) {
            $this->output->writeln('Cache fal_bynder not found. Please check your cache configuration or DB tables.');
        }
    }

    protected function synchronizeStorages(): void
    {
        $storages = $this->getBynderStorages();
        if ($storages === []) {
            $this->output->writeln('No bynder storages found.');
            return;
        }

        foreach ($storages as $storage) {
            $this->synchronizeStorage($storage);
            $this->output->writeln(
                sprintf(
                    'Synchronized %d files in bynder storage %d',
                    $this->countFilesInStorage($storage),
                    $storage->getUid()
                )
            );
        }
    }

    protected function synchronizeStorage(ResourceStorage $storage): void
    {
        $start = 0;
        $maxNumberOfItems = 200;
        $folder = $storage->getRootLevelFolder();
        try {
            while ($storage->getFilesInFolder($folder, $start, $maxNumberOfItems) !== []) {
                $start += $maxNumberOfItems;
            }
        } catch (InsufficientFolderAccessPermissionsException $insufficientFolderAccessPermissionsException) {
            $this->output->writeln('CLI user does not have permission to access bynder storage');
        }
    }

    protected function countFilesInStorage(ResourceStorage $storage): int
    {
        try {
            return $storage->countFilesInFolder($storage->getRootLevelFolder());
        } catch (InsufficientFolderAccessPermissionsException $insufficientFolderAccessPermissionsException) {
            $this->output->writeln('CLI user does not have permission to count files of bynder storage');
        }

        return 0;
    }

    /**
     * @return ResourceStorage[]
     */
    protected function getBynderStorages(): array
    {
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);

        return $storageRepository->findByStorageType('fal_bynder');
    }
}
