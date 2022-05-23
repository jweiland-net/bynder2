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
use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;

class GeneratePublicUrlForResourceEventListener
{
    public function __invoke(GeneratePublicUrlForResourceEvent $event): void
    {
        $bynderDriver = $event->getDriver();
        if (!$bynderDriver instanceof BynderDriver) {
            return;
        }

        $fileInfoResponse = $bynderDriver->getMediaDownloadResponse($event->getResource()->getIdentifier());

        $event->setPublicUrl($fileInfoResponse['s3_file'] ?? '');
    }
}
