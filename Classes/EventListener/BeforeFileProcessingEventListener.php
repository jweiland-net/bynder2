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
use TYPO3\CMS\Core\Utility\GeneralUtility;

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

        $event->getProcessedFile()->updateProperties([
            'width' => $configuration['width'],
            'height' => $configuration['height']
        ]);

        if ($processingUrl = $bynderDriver->getProcessingUrl($event->getFile(), $configuration, $fileInfoResponse)) {
            $event->getProcessedFile()->updateProcessingUrl($processingUrl);
        }
    }

    protected function getUpdatedConfiguration(array $configuration, array $fileInfoResponse): array
    {
        $options = [
            'noScale' => true
        ];

        if (isset($configuration['maxWidth'])) {
            $options['maxW'] = $configuration['maxWidth'];
        }
        if (isset($configuration['maxHeight'])) {
            $options['maxH'] = $configuration['maxHeight'];
        }

        $graphicalFunctions = GeneralUtility::makeInstance(GraphicalFunctions::class);
        $sizes = $graphicalFunctions->getImageScale(
            [
                0 => $fileInfoResponse['width'] ?? '0', // 0 => width. Don't change array key!
                1 => $fileInfoResponse['height'] ?? '0' // 1 => height. Don't change array key!
            ],
            (string)$configuration['width'], // string to keep "m" and "c" options
            (string)$configuration['height'], // string to keep "m" and "c" options
            $options
        );

        $configuration['width'] = (int)($sizes[0] ?? 0);
        $configuration['height'] = (int)($sizes[1] ?? 0);

        return $configuration;
    }
}
