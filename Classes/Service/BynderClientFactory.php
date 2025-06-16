<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Service;

use Bynder\Api\BynderClient;
use JWeiland\Bynder2\Client\BynderClientWrapper;

final readonly class BynderClientFactory
{
    public function __construct(
        private BynderTokenFactory $bynderTokenFactory,
        private BynderFalConfigurationFactory $bynderFalConfigurationFactory,
    ) {}

    /**
     * Instantiates a Bynder client for interacting with the Bynder API.
     *
     * @throws \Exception Thrown if the provided configuration is invalid.
     */
    public function createClient(array $configuration): BynderClient
    {
        $bynderFalConfiguration = $this->bynderFalConfigurationFactory->createConfiguration($configuration);

        return new BynderClient(
            $this->bynderTokenFactory->createToken($bynderFalConfiguration)->getToken()
        );
    }

    /**
     * Creates a wrapper around the Bynder client to provide access to the modified AccessToken class.
     * This class includes an updated "expire" property after the first request, which serves as additional
     * information for EXT:backend form elements.
     *
     * @throws \Exception Thrown when the provided configuration is invalid.
     */
    public function createClientWrapper(array $configuration): BynderClientWrapper
    {
        $bynderFalConfiguration = $this->bynderFalConfigurationFactory->createConfiguration($configuration);
        $bynderTokenConfiguration = $this->bynderTokenFactory->createToken($bynderFalConfiguration);

        return new BynderClientWrapper(
            new BynderClient($bynderTokenConfiguration->getToken()),
            $bynderTokenConfiguration
        );
    }
}
