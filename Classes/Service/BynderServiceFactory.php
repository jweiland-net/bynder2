<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Service;

use JWeiland\Bynder2\Service\Exception\InvalidBynderConfigurationException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Factory to retrieve a configured BynderService
 */
class BynderServiceFactory
{
    /**
     * @throws InvalidBynderConfigurationException
     */
    public function getBynderServiceForConfiguration(array $configuration): BynderService
    {
        $bynderService = $this->getBynderService();
        $bynderService->setConfiguration($configuration);

        return $bynderService;
    }

    /**
     * Keep an eye on Services.yaml as this class should be defined as prototype/shared
     */
    protected function getBynderService(): BynderService
    {
        return GeneralUtility::makeInstance(BynderService::class);
    }
}
