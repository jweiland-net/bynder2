<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Client;

use Bynder\Api\BynderClient;
use JWeiland\Bynder2\Configuration\BynderTokenConfigurationInterface;

/**
 * This wrapper addresses the issue related to the modified AccessToken. During the first request,
 * the BynderClient internally updates the AccessToken instance. As a result, it is no longer possible
 * to retrieve the "expire" information directly. To handle this, class references are utilized.
 * When the AccessToken is modified within the BynderClient, the corresponding AccessToken in the
 * BynderTokenConfiguration is also updated. This implementation ensures the "expire" value can
 * be extracted reliably from the updated reference.
 */
readonly class BynderClientWrapper
{
    public function __construct(
        private BynderClient $bynderClient,
        private BynderTokenConfigurationInterface $bynderTokenConfiguration,
    ) {}

    public function getBynderClient(): BynderClient
    {
        return $this->bynderClient;
    }

    public function getBynderTokenConfiguration(): BynderTokenConfigurationInterface
    {
        return $this->bynderTokenConfiguration;
    }

    /**
     * This method will return a public URL to the original file.
     * No crop, no resize, no thumbnail.
     *
     * @throws \Exception If bynder cannot create a S3 CDN URL, it results in an exception
     */
    public function getCdnDownloadUrl(string $fileIdentifier): string
    {
        $remoteFileResponse = $this->bynderClient->getAssetBankManager()->getMediaDownloadLocation(
            $fileIdentifier
        )->wait();

        return $remoteFileResponse['s3_file'] ?? '';
    }
}
