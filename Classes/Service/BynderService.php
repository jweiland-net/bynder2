<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Service;

use Bynder\Api\BynderApiFactory;
use Bynder\Api\Impl\BynderApi;
use JWeiland\Bynder2\Configuration\ExtConf;
use JWeiland\Bynder2\Service\Exception\InvalidBynderConfigurationException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Recordlist\Browser\FileBrowser;

/*
 *
 */
class BynderService
{
    /**
     * @var BynderApi|null
     */
    protected $bynderClient;

    /**
     * @var ExtConf|null
     */
    protected $extConf;

    /**
     * @throws InvalidBynderConfigurationException
     */
    public function __construct(array $configuration)
    {
        if (!$this->isValidConfiguration($configuration)) {
            throw new InvalidBynderConfigurationException(
                'BynderService was constructed with an invalid configuration. Please check credentials in sys_file_storage record for Bynder',
                1653982728
            );
        }

        $this->bynderClient = BynderApiFactory::create(
            [
                'consumerKey' => $configuration['consumer_key'],
                'consumerSecret' => $configuration['consumer_secret'],
                'token' => $configuration['token_key'],
                'tokenSecret' => $configuration['token_secret'],
                'baseUrl' => $configuration['url'],
            ]
        );

        $this->extConf = GeneralUtility::makeInstance(ExtConf::class);
    }

    protected function isValidConfiguration(array $configuration): bool
    {
        return isset(
            $configuration['consumer_key'],
            $configuration['consumer_secret'],
            $configuration['token_key'],
            $configuration['token_secret']
        );
    }

    public function getFiles(int $start = 1, int $numberOfFiles = 40, string $orderBy = ''): array
    {
        // This is the limit of Bynder API
        $maxFilesEachRequest = 1000;

        $files = [];
        $start = MathUtility::forceIntegerInRange($start, 1);

        if ($numberOfFiles === 0 && $this->isFileBrowserCall()) {
            $numberOfFiles = $this->extConf->getNumberOfFilesInFileBrowser();
        }

        if ($numberOfFiles === 0) {
            $limit = $maxFilesEachRequest;
            $page = (int)floor($start / $maxFilesEachRequest) + 1;
        } else {
            $limit = MathUtility::forceIntegerInRange($numberOfFiles, 1, $maxFilesEachRequest);
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

            while ($bynderFiles = $this->bynderClient->getAssetBankManager()->getMediaList($options)->wait()) {
                array_push($files, ...$bynderFiles);

                // Prevent calling the Bynder API again
                if (MathUtility::isIntegerInRange($numberOfFiles, 1, $maxFilesEachRequest)) {
                    break;
                }

                // $numberOfFiles = 0. Prepare for next API call
                $start += $maxFilesEachRequest;
                $options['page'] = (int)floor($start / $maxFilesEachRequest) + 1;
            }
        } catch (\Exception $exception) {
        }

        return $files;
    }

    public function getFile(string $fileIdentifier): array
    {
        $file = [];
        try {
            $file = $this->bynderClient->getAssetBankManager()->getMediaInfo(
                $fileIdentifier
            )->wait();
        } catch (\Exception $exception) {
            // File does not exists
            return $file;
        }

        return (($file['statuscode'] ?? '200') === '200') ? $file : [];
    }

    public function countFiles(): int
    {
        $mediaUsage = $this->bynderClient->getAssetBankManager()->getMediaList([
            'count' => 0,
            'limit' => 0,
            'total' => 1,
            'includeMediaItems' => 0,
            'isPublic' => 0,
            'archive' => 0,
        ])->wait();

        return $mediaUsage['total']['count'] ?? 0;
    }

    /**
     * This method will return a public URL to the original file.
     * No crop, no resize, no thumbnail.
     */
    public function getCdnDownloadUrl(string $fileIdentifier): string
    {
        static $cdnDownloadUrlCache = [];

        if (array_key_exists($fileIdentifier, $cdnDownloadUrlCache)) {
            return $cdnDownloadUrlCache[$fileIdentifier];
        }

        $cdnDownloadUrl = '';
        try {
            $remoteFileResponse = $this->bynderClient->getAssetBankManager()->getMediaDownloadLocation(
                $fileIdentifier
            )->wait();
            $cdnDownloadUrl = $remoteFileResponse['s3_file'] ?? '';
        } catch (\Exception $exception) {
            // If file was not found at Bynder, it reacts with an Exception
        }

        $cdnDownloadUrlCache[$fileIdentifier] = $cdnDownloadUrl;

        return $cdnDownloadUrl;
    }

    /**
     * If call comes from FileBrowser the number of files is not limited. As Bynder works with just ONE folder
     * you will always get ALL files of Bynder, which can be a lot of files to render, slows down performance and may
     * break rendering process (Error 500)
     *
     * @return bool
     */
    protected function isFileBrowserCall(): bool
    {
        $backTrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 7);
        foreach ($backTrace as $entry) {
            if ($entry['class'] === FileBrowser::class) {
                return true;
            }
        }

        return false;
    }
}
