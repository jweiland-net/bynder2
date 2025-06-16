<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Resource;

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\ExtractorInterface;

/**
 * Since Bynder does not provide local storage, a custom extractor service is utilized
 * to retrieve metadata such as width, height, and copyright for sys_file_metadata.
 */
final readonly class BynderExtractor implements ExtractorInterface
{
    public function __construct(
        private FrontendInterface $cache,
    ) {}

    /**
     * @return array<int, string>
     */
    public function getFileTypeRestrictions(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    public function getDriverRestrictions(): array
    {
        return [
            'bynder2',
        ];
    }

    public function getPriority(): int
    {
        return 70;
    }

    public function getExecutionPriority(): int
    {
        return 50;
    }

    public function canProcess(File $file): bool
    {
        return true;
    }

    /**
     * @param array<string, int|string> $previousExtractedData Contains the extracted data from possible previous extractors.
     * @return array<string, int|string>
     */
    public function extractMetaData(File $file, array $previousExtractedData = []): array
    {
        if (!$this->cache->has($file->getIdentifier())) {
            return [];
        }

        $fileResponse = $this->cache->get($file->getIdentifier());

        $fileMetaData = [
            'title' => $fileResponse['name'] ?? '',
            'description' => $fileResponse['description'] ?? '',
            'width' => $fileResponse['width'] ?? '',
            'height' => $fileResponse['height'] ?? '',
            'copyright' => $fileResponse['copyright'] ?? '',
            'keywords' => implode(', ', $fileResponse['tags'] ?? []),
            'bynder2_thumb_mini' => $fileResponse['thumbnails']['mini'] ?? '',
            'bynder2_thumb_thul' => $fileResponse['thumbnails']['thul'] ?? '',
            'bynder2_thumb_webimage' => $fileResponse['thumbnails']['webimage'] ?? '',
        ];

        return array_merge($previousExtractedData, $fileMetaData);
    }
}
