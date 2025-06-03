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
use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;
use TYPO3\CMS\Core\Resource\FileInterface;

class GeneratePublicUrlForResourceEventListener
{
    public function __invoke(GeneratePublicUrlForResourceEvent $event): void
    {
        $bynderDriver = $event->getDriver();
        $file = $event->getResource();

        if (!$bynderDriver instanceof BynderDriver) {
            return;
        }

        if (!$file instanceof FileInterface) {
            return;
        }

        $event->setPublicUrl($file->getProperty('bynder2_thumb_webimage'));
    }
}
