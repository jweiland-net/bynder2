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
use League\OAuth2\Client\Token\AccessToken;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class BynderAccessTokenConfiguration implements BynderTokenConfigurationInterface
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

        return new AccessTokenConfiguration(
            $this->bynderFalConfiguration->getUrl(),
            $this->bynderFalConfiguration->getRedirectCallback(),
            $this->bynderFalConfiguration->getClientId(),
            $this->bynderFalConfiguration->getClientSecret(),
            $this->getAccessToken(),
            $this->guzzleConfiguration->getConfiguration($GLOBALS['TYPO3_CONF_VARS']['HTTP'])
        );
    }

    private function getAccessToken(): ?AccessToken
    {
        if ($this->bynderFalConfiguration->getAccessToken() !== ''
            && $this->bynderFalConfiguration->getRefreshToken() !== ''
            && $this->bynderFalConfiguration->getExpires() !== 0
        ) {
            return new AccessToken([
                'access_token' => $this->bynderFalConfiguration->getAccessToken(),
                'refresh_token' => $this->bynderFalConfiguration->getRefreshToken(),
                'expires' => $this->bynderFalConfiguration->getExpires(),
            ]);
        }

        return null;
    }

    private function isValidConfiguration(): bool
    {
        // We have to add "https://" here temporarily, as needed by "isValidUrl"
        return GeneralUtility::isValidUrl('https://' . $this->bynderFalConfiguration->getUrl())
            && $this->bynderFalConfiguration->getRedirectCallback() !== ''
            && $this->bynderFalConfiguration->getClientId() !== ''
            && $this->bynderFalConfiguration->getClientSecret() !== '';
    }
}
