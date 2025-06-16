<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Configuration;

final readonly class BynderFalConfiguration
{
    public function __construct(
        private string $url = '',
        private string $permanentToken = '',
        private string $redirectCallback = '',
        private string $clientId = '',
        private string $clientSecret = '',
        private string $authorizationUrl = '',
        private string $accessToken = '',
        private string $refreshToken = '',
        private int $expires = 0,
    ) {}

    public function getUrl(): string
    {
        return str_replace(['http://', 'https://'], '', $this->url);
    }

    public function getPermanentToken(): string
    {
        return $this->permanentToken;
    }

    public function getRedirectCallback(): string
    {
        return $this->redirectCallback;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function getAuthorizationUrl(): string
    {
        return $this->authorizationUrl;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function getExpires(): int
    {
        return $this->expires;
    }
}
