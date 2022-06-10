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
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Filelist\FileFacade;

class GeneratePublicUrlForResourceEventListener
{
    public function __invoke(GeneratePublicUrlForResourceEvent $event): void
    {
        $bynderDriver = $event->getDriver();
        if (!$bynderDriver instanceof BynderDriver) {
            return;
        }

        // Customer has searched for keywords which results in thousands of files. As we currently do not cache the
        // publicUrl, rendering of the file list needs ages, because for each file the Bynder API has to be called
        // to get the public URL. Set publicUrl to empty string here, will prevent Fluid from rendering
        // the public URL. See EXT:filelist/Resources/Private/Templates/FileList/Search.html.

        // Since TYPO3 11.3 the FileListController gets a huge refactoring. No more Fluid rendering. So,
        // setting publicUrl to empty string is not needed anymore.
        $typo3Branch = GeneralUtility::makeInstance(Typo3Version::class)->getBranch();
        if (
            version_compare($typo3Branch, '11.3', '<')
            && $this->isSearchCall()
        ) {
            $publicUrl = '';
        } else {
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
        $backTrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 10);
        foreach ($backTrace as $entry) {
            if ($entry['class'] === FileFacade::class && $entry['function'] === 'getPublicUrl') {
                return true;
            }
        }

        return false;
    }
}
