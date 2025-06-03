<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Service;

use JWeiland\Bynder2\Configuration\BynderAccessTokenConfiguration;
use JWeiland\Bynder2\Configuration\BynderFalConfiguration;
use JWeiland\Bynder2\Configuration\BynderPermanentTokenConfiguration;
use JWeiland\Bynder2\Configuration\BynderTokenConfigurationInterface;
use JWeiland\Bynder2\Configuration\GuzzleConfiguration;

/**
 * AccessToken and PermanentToken are completely different and don't implement an interface. We wrapped them
 * into own classes with an interface to streamline them a bit.
 * Use "getToken()" to retrieve the original bynder token configuration class again.
 */
class BynderTokenFactory
{
    public function createToken(BynderFalConfiguration $bynderFalConfiguration): BynderTokenConfigurationInterface
    {
        $guzzleConfiguration = new GuzzleConfiguration();

        if ($bynderFalConfiguration->getPermanentToken() !== '') {
            return new BynderPermanentTokenConfiguration($bynderFalConfiguration, $guzzleConfiguration);
        }

        return new BynderAccessTokenConfiguration($bynderFalConfiguration, $guzzleConfiguration);
    }
}
