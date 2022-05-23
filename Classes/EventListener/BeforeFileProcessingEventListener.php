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

        $processingUrl = $bynderDriver->getProcessingUrl(
            $event->getFile()->getIdentifier(),
            $event->getConfiguration()
        );

        if ($processingUrl) {
            $event->getProcessedFile()->updateProperties([
                'width' => $event->getConfiguration()['width'] ?? 0,
                'height' => $event->getConfiguration()['height'] ?? 0
            ]);
            $event->getProcessedFile()->updateProcessingUrl($processingUrl);
        }
    }
}
