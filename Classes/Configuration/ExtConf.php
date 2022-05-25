<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Configuration;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\SingletonInterface;

/*
 * This class streamlines all settings from extension manager
 */
class ExtConf implements SingletonInterface
{
    /**
     * @var int
     */
    protected $numberOfFilesInFileBrowser = 100;

    public function __construct(ExtensionConfiguration $extensionConfiguration)
    {
        $extConf = $extensionConfiguration->get('bynder2');
        if (is_array($extConf)) {
            // call setter method foreach configuration entry
            foreach ($extConf as $key => $value) {
                $methodName = 'set' . ucfirst($key);
                if (method_exists($this, $methodName)) {
                    $this->$methodName((string)$value);
                }
            }
        }
    }

    public function getNumberOfFilesInFileBrowser(): int
    {
        return $this->numberOfFilesInFileBrowser;
    }

    public function setNumberOfFilesInFileBrowser(string $numberOfFilesInFileBrowser): void
    {
        $this->numberOfFilesInFileBrowser = (int)$numberOfFilesInFileBrowser;
    }
}
