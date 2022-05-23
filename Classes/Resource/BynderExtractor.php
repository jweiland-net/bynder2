<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\FalBynder\Resource;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\ExtractorInterface;

/**
 * As Bynder is no local storage we have to use our own extractor service to retrieve
 * metadata for sys_file_metadata like width, height, ...
 */
class BynderExtractor implements ExtractorInterface
{
    public function getFileTypeRestrictions(): array
    {
        return [];
    }

    public function getDriverRestrictions(): array
    {
        return [
            'fal_bynder'
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

    public function extractMetaData(File $file, array $previousExtractedData = []): array
    {
        $fileInfo = $file->getStorage()->getFileInfoByIdentifier(
            $file->getIdentifier(),
            [
                'title',
                'description',
                'width',
                'height',
                'copyright',
                'keywords',
            ]
        );

        return array_merge($previousExtractedData, $fileInfo);
    }
}
