<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Service;

use JWeiland\Bynder2\Configuration\BynderFalConfiguration;

class BynderFalConfigurationFactory
{
    /**
     * @param array $configuration The configuration from bynder FAL driver. Can also be the result of a sys_file_storage request
     */
    public function createConfiguration(array $configuration): BynderFalConfiguration
    {
        return new BynderFalConfiguration(
            $configuration['url'] ?? '',
            $configuration['permanentToken'] ?? '',
            $configuration['redirectCallback'] ?? '',
            $configuration['clientId'] ?? '',
            $configuration['clientSecret'] ?? '',
            $configuration['authorizationUrl'] ?? '',
            $configuration['accessToken'] ?? '',
            $configuration['refreshToken'] ?? '',
            (int)($configuration['expires'] ?? 0),
        );
    }
}
