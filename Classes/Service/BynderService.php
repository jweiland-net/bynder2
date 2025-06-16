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
use JWeiland\Bynder2\Configuration\ExtConf;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Service to connect to the Bynder API and retrieving the files
 */
class BynderService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Max files to retrieve from Bynder API.
     * Currently, 1000 is max.
     */
    public const MAX_FILES_EACH_REQUEST = 500;

    public function __construct(
        protected readonly ExtConf $extConf,
    ) {}

    public function getAuthorizationUrl(BynderClient $bynderClient): string
    {
        // "meta.assetbank:read" results in "Invalid scope", so for now I have deactivated it.
        return $bynderClient->getAuthorizationUrl([
            'offline',
            'current.user:read',
            'current.profile:read',
            'asset:read',
            'asset:write',
            //'meta.assetbank:read',
            'asset.usage:read',
            'asset.usage:write',
        ]);
    }

    public function getCurrentUser(BynderClient $bynderClient): array
    {
        try {
            return $bynderClient->getCurrentUser()->wait();
        } catch (\Exception $e) {
            $this->logger->error('Current user could not be fetched by bynder API', ['exception' => $e]);
            return [];
        }
    }

    public function getFiles(
        BynderClient $bynderClient,
        int $start = 1,
        int $numberOfFiles = 40,
        string $orderBy = ''
    ): \Generator {
        $start = MathUtility::forceIntegerInRange($start, 1);

        if ($numberOfFiles === 0) {
            $limit = self::MAX_FILES_EACH_REQUEST;
            $page = (int)floor($start / self::MAX_FILES_EACH_REQUEST) + 1;
        } else {
            $limit = MathUtility::forceIntegerInRange($numberOfFiles, 1, self::MAX_FILES_EACH_REQUEST);
            $page = (int)floor($start / $limit) + 1;
        }

        try {
            $options = [
                'page' => $page,
                'limit' => $limit,
                'orderBy' => $orderBy,
                'includeMediaItems' => 1,
                'isPublic' => 0,
                'archive' => 0,
            ];

            while ($bynderFiles = $bynderClient->getAssetBankManager()->getMediaList($options)->wait()) {
                if (!is_array($bynderFiles)) {
                    break;
                }

                $this->logger->debug('Retrieved ' . self::MAX_FILES_EACH_REQUEST . ' files from Bynder API');

                // Try to keep the memory low.
                // Not confirmed, but it may happen that EXT:bynder2 will be blocked temporarily by Bynder server as
                // it starts to many requests to retrieve all files. To prevent that, we have migrated to a
                // PHP generator yield solution. In that case just one request starts, and we process the resulting
                // files first before requesting the next bunch of files.
                foreach ($bynderFiles as $bynderFile) {
                    yield $bynderFile['id'] => $bynderFile;
                }

                // Prevent calling the Bynder API again
                if (MathUtility::isIntegerInRange($numberOfFiles, 1, self::MAX_FILES_EACH_REQUEST)) {
                    break;
                }

                // $numberOfFiles = 0. Prepare for the next API call
                $start += self::MAX_FILES_EACH_REQUEST;
                $options['page'] = (int)floor($start / self::MAX_FILES_EACH_REQUEST) + 1;
            }
        } catch (\Exception $exception) {
            $this->logger->error('Bynder API error: ' . $exception->getMessage(), ['exception' => $exception]);
        }
    }
}
