<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Repository;

use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;

/**
 * Instantiates an object storage that aggregates TYPO3 ResourceStorage instances with the driver type "bynder2".
 * Defined in Services.yaml to be accessible by other services.
 */
readonly class BynderStorageFactory
{
    public function __construct(
        private StorageRepository $storageRepository,
    ) {}

    /**
     * @return \SplObjectStorage<ResourceStorage, mixed>
     */
    public function getBynderStorages(): \SplObjectStorage
    {
        /** @var \SplObjectStorage<ResourceStorage, mixed> $bynderStorages */
        $bynderStorages = new \SplObjectStorage();

        foreach ($this->storageRepository->findByStorageType('bynder2') as $bynderStorage) {
            $bynderStorages->attach($bynderStorage);
        }

        return $bynderStorages;
    }
}
