<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/fal-bynder.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\FalBynder\EventListener;

use JWeiland\FalBynder\Driver\BynderDriver;
use TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent;

class BeforeFileProcessingEventListener
{
    public function __invoke(BeforeFileProcessingEvent $event): void
    {
        $bynderDriver = $event->getDriver();
        if (!$bynderDriver instanceof BynderDriver) {
            return;
        }

        $fileInfoResponse = $bynderDriver->getFileInfoResponse($event->getFile()->getIdentifier());
        $configuration = $this->getUpdatedConfiguration($event->getConfiguration(), $fileInfoResponse);

        $processingUrl = $bynderDriver->getProcessingUrl(
            $event->getFile()->getIdentifier(),
            $configuration,
            $fileInfoResponse
        );

        if ($processingUrl) {
            $event->getProcessedFile()->updateProperties([
                'width' => $configuration['width'],
                'height' => $configuration['height']
            ]);
            $event->getProcessedFile()->updateProcessingUrl($processingUrl);
        }
    }

    protected function getUpdatedConfiguration(array $configuration, array $fileInfoResponse): array
    {
        $width = (int)($configuration['width'] ?? $configuration['maxWidth'] ?? 0);
        $height = (int)($configuration['height'] ?? $configuration['maxHeight'] ?? 0);
        if ($width === 0 && $height === 0) {
            return $configuration;
        }

        if ((int)($fileInfoResponse['width'] ?? 0) === 0 && (int)($fileInfoResponse['height'] ?? 0) === 0) {
            return $configuration;
        }

        // Remove "m" and "c" and set as new defaults
        $configuration['height'] = (int)($configuration['height'] ?? $height);
        $configuration['width'] = (int)($configuration['width'] ?? $width);

        if ($width === 0) {
            $configuration['width'] = (int)ceil($height / $fileInfoResponse['height'] * $fileInfoResponse['width']);
            $configuration['height'] = $configuration['height'] ?? $height;
        } elseif ($height === 0) {
            $configuration['width'] = $configuration['width'] ?? $width;
            $configuration['height'] = (int)ceil($width / $fileInfoResponse['width'] * $fileInfoResponse['height']);
        }

        return $configuration;
    }
}
