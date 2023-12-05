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
 * After authorization of Bynder APP you will be redirected back to the URL configured in redirectCallback. In most
 * cases this should be the TYPO3 backend. This middleware will catch such redirects and shows the bynder code
 * you have to add at the Bynder sys_file_storage record.
 */
class BynderStorageRepository
{
    /**
     * @var \SplObjectStorage|ResourceStorage[]
     */
    protected $bynderStorages;

    public function __construct(StorageRepository $storageRepository)
    {
        $this->bynderStorages = new \SplObjectStorage();

        foreach ($storageRepository->findByStorageType('bynder2') as $bynderStorage) {
            $this->bynderStorages->attach($bynderStorage);
        }
    }

    /**
     * @return \SplObjectStorage|ResourceStorage[]
     */
    public function getBynderStorages(): \SplObjectStorage
    {
        return $this->bynderStorages;
    }
}
