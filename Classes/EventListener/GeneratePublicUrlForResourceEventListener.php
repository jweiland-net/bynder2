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
use JWeiland\Bynder2\Service\BynderClientFactory;
use JWeiland\Bynder2\Service\BynderService;
use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;

class GeneratePublicUrlForResourceEventListener
{
    public function __construct(
        private readonly BynderClientFactory $bynderClientFactory,
        private readonly BynderService $bynderService,
    ) {}

    public function __invoke(GeneratePublicUrlForResourceEvent $event): void
    {
        $bynderDriver = $event->getDriver();
        if (!$bynderDriver instanceof BynderDriver) {
            return;
        }

        // Customer has searched for keywords that result in thousands of files. As we currently do not cache the
        // publicUrl, rendering of the file list needs ages, because for each file the Bynder API has to be called
        // to get the public URL. Set publicUrl to empty string here, will prevent Fluid from rendering
        // the public URL. See EXT:filelist/Resources/Private/Templates/FileList/Search.html.
        $publicUrl = $this->bynderService->getCdnDownloadUrl(
            $this->bynderClientFactory->createClient($event->getStorage()->getConfiguration()),
            $event->getResource()->getIdentifier()
        );

        $event->setPublicUrl($publicUrl);
    }
}
