<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Configuration;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\MathUtility;

/*
 * This class streamlines all settings from the extension manager
 */
#[Autoconfigure(constructor: 'create')]
final class ExtConf
{
    private const EXT_KEY = 'bynder2';

    private const DEFAULT_SETTINGS = [
        'numberOfFilesInFileBrowser' => 100,
    ];

    public function __construct(
        private readonly int $numberOfFilesInFileBrowser = self::DEFAULT_SETTINGS['numberOfFilesInFileBrowser'],
    ) {}

    public static function create(ExtensionConfiguration $extensionConfiguration): self
    {
        $extensionSettings = self::DEFAULT_SETTINGS;

        // Overwrite default extension settings with values from EXT_CONF
        try {
            $extensionSettings = array_merge(
                $extensionSettings,
                $extensionConfiguration->get(self::EXT_KEY),
            );
        } catch (ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException) {
        }

        $extensionSettings['numberOfFilesInFileBrowser'] = MathUtility::forceIntegerInRange(
            $extensionSettings['numberOfFilesInFileBrowser'],
            1,
            1000,
            100
        );

        return new self(
            numberOfFilesInFileBrowser: $extensionSettings['numberOfFilesInFileBrowser'],
        );
    }

    public function getNumberOfFilesInFileBrowser(): int
    {
        return $this->numberOfFilesInFileBrowser;
    }
}
