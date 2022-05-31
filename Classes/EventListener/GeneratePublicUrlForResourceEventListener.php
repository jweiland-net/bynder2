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
use TYPO3\CMS\Filelist\FileFacade;

class GeneratePublicUrlForResourceEventListener
{
    public function __invoke(GeneratePublicUrlForResourceEvent $event): void
    {
        $bynderDriver = $event->getDriver();
        if (!$bynderDriver instanceof BynderDriver) {
            return;
        }

        $publicUrl = '';
        if (!$this->isSearchCall()) {
            $publicUrl = $bynderDriver->getBynderService()->getCdnDownloadUrl(
                $event->getResource()->getIdentifier()
            );
        }

        $event->setPublicUrl($publicUrl);
    }

    /**
     * In case of filelist search a fluid template will be used to render the list of records.
     * Instead of XClassing filelist controller and change templates we check the PHP calling history.
     * FileFacades are only used in filelist search.
     *
     * @return bool
     */
    protected function isSearchCall(): bool
    {
        $backTrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 7);
        foreach ($backTrace as $entry) {
            if ($entry['class'] === FileFacade::class && $entry['function'] === 'getPublicUrl') {
                return true;
            }
        }

        return false;
    }
}
