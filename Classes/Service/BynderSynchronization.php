<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Service;

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BynderSynchronization
{
    public function __construct(
        private readonly BynderService $bynderService,
        private readonly BynderClientFactory $bynderClientFactory,
        private readonly FrontendInterface $cache,
    ) {}

    public function synchronizeStorage(ResourceStorage $bynderStorage): int
    {
        $client = $this->bynderClientFactory->createClient($bynderStorage->getConfiguration());

        $files = 0;
        $indexer = $this->getIndexer($bynderStorage);
        foreach ($this->bynderService->getFiles($client, 0, 0) as $file) {
            // The createIndexEntry method utilizes the Bynder driver to verify the existence of a file and retrieve
            // its metadata. To access the file information, a transient cache is provided to facilitate this process.
            $this->cache->set($file['id'], $file);
            $indexer->createIndexEntry($file['id']);
            $this->cache->remove($file['id']);
        }

        return $files;
    }

    private function getIndexer(ResourceStorage $resourceStorage): Indexer
    {
        return GeneralUtility::makeInstance(Indexer::class, $resourceStorage);
    }
}
