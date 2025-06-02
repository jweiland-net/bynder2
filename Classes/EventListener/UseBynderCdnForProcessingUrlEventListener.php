<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\EventListener;

use JWeiland\Bynder2\Driver\BynderDriver;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;

/**
 * The Bynder API provides default thumbnails in three predefined image sizes. Instead of downloading and manually
 * resizing each image to match the required dimensions, we can directly utilize these pre-configured thumbnails
 * from the API.
 *
 * However, the `processing_url` of `sys_file_processedfile` cannot be persistently stored (refer to: `ProcessedFile::toArray`),
 * which necessitates reassigning this value during each request. Additionally, since the processed file is linked
 * to the original file, the image size defaults to that of the original, which may span thousands of pixels.
 *
 * To address this, this class temporarily adjusts the image dimensions to align with the requested processing
 * configuration, ensuring the correct size is used without manual intervention.
 */
class UseBynderCdnForProcessingUrlEventListener
{
    private const UNAVAILABLE_IMAGE_PATH = 'EXT:bynder/Resources/Public/Icons/ImageUnavailable.svg';

    public function __construct(
        private readonly GraphicalFunctions $graphicalFunctions,
    ) {}

    public function __invoke(BeforeFileProcessingEvent $event): void
    {
        $bynderDriver = $event->getDriver();
        $processedFile = $event->getProcessedFile();
        $processingConfiguration = $event->getConfiguration();

        if (!$bynderDriver instanceof BynderDriver) {
            return;
        }

        $configurationRespectingImageScale = $this->getConfigurationRespectingScale($processingConfiguration, $event->getFile());

        if ($processingUrl = $this->getProcessingUrl($processedFile, $configurationRespectingImageScale)) {
            $processedFile->updateProcessingUrl($processingUrl);
            $event->getProcessedFile()->updateProperties([
                'width' => $configurationRespectingImageScale['width'],
                'height' => $configurationRespectingImageScale['height'],
            ]);
        }
    }

    protected function getConfigurationRespectingScale(array $processingConfiguration, FileInterface $file): array
    {
        $options = [
            'noScale' => true
        ];

        if (isset($processingConfiguration['maxWidth'])) {
            $options['maxW'] = $processingConfiguration['maxWidth'];
        }
        if (isset($processingConfiguration['maxHeight'])) {
            $options['maxH'] = $processingConfiguration['maxHeight'];
        }

        $sizes = $this->graphicalFunctions->getImageScale(
            [
                0 => $file->getProperty('width') ?? '0', // 0 => width. Don't change the array key!
                1 => $file->getProperty('height') ?? '0' // 1 => height. Don't change the array key!
            ],
            (string)($processingConfiguration['width'] ?? ''), // string to keep "m" and "c" options
            (string)($processingConfiguration['height'] ?? ''), // string to keep "m" and "c" options
            $options
        );

        return array_merge(
            $processingConfiguration,
            [
                'width' => (int)($sizes[0] ?? 0),
                'height' => (int)($sizes[1] ?? 0),
            ]
        );
    }

    /**
     * Bynder delivers 3 pre-configured thumbnails over its CDN.
     * Check if we can use them, for faster rendering.
     *
     * Must be public URL
     */
    public function getProcessingUrl(ProcessedFile $processedFile, array $configurationRespectingImageScale): string
    {
        if ($configurationRespectingImageScale['width'] <= 80) {
            $processingUrl = $processedFile->getProperty('bynder2_thumb_mini') ?? '';
        } elseif ($configurationRespectingImageScale['width'] <= 250) {
            $processingUrl = $processedFile->getProperty('bynder2_thumb_thul') ?? '';
        } elseif ($configurationRespectingImageScale['width'] <= 800) {
            $processingUrl = $processedFile->getProperty('bynder2_thumb_webimage') ?? '';
        } else {
            $processingUrl = self::UNAVAILABLE_IMAGE_PATH;
        }

        return $processingUrl;
    }
}
