<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Configuration;

use Bynder\Api\Impl\OAuth2\Configuration as AccessTokenConfiguration;
use Bynder\Api\Impl\PermanentTokens\Configuration as PermanentTokenConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class BynderPermanentTokenConfiguration implements BynderTokenConfigurationInterface
{
    public function __construct(
        private BynderFalConfiguration $bynderFalConfiguration,
        private GuzzleConfiguration $guzzleConfiguration,
    ) {}

    public function getToken(): AccessTokenConfiguration|PermanentTokenConfiguration|null
    {
        if (!$this->isValidConfiguration()) {
            return null;
        }

        return new PermanentTokenConfiguration(
            $this->bynderFalConfiguration->getUrl(),
            $this->bynderFalConfiguration->getPermanentToken(),
            $this->guzzleConfiguration->getConfiguration($GLOBALS['TYPO3_CONF_VARS']['HTTP']),
        );
    }

    private function isValidConfiguration(): bool
    {
        // We have to add "https://" here temporarily, as needed by "isValidUrl"
        return GeneralUtility::isValidUrl('https://' . $this->bynderFalConfiguration->getUrl())
            && $this->bynderFalConfiguration->getPermanentToken() !== '';
    }
}
