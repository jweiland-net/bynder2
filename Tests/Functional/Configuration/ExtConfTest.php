<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Tests\Functional\Configuration;

use JWeiland\Bynder2\Configuration\ExtConf;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case.
 */
class ExtConfTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/bynder2',
    ];

    public static function numberOfFilesDataProvider(): array
    {
        return [
            'Number with prepended text' => ['123Test', 123],
            'Number with spaces' => ['   249  ', 249],
            'Correct number' => ['234', 234],
            'Invalid negative number' => ['-12', 1],
            'Invalid positive number' => ['12000', 1000],
            'Force 0 to default value 100' => ['0', 100],
            'Force empty string to default value 100' => ['', 100],
        ];
    }

    #[Test]
    public function getNumberOfFilesInFileBrowserInitiallyReturns100(): void
    {
        $extConf = new ExtConf();

        self::assertSame(
            100,
            $extConf->getNumberOfFilesInFileBrowser()
        );
    }

    #[Test]
    #[DataProvider('numberOfFilesDataProvider')]
    public function setNumberOfFilesInFileBrowserWithStringResultsInInteger($value, $expected): void
    {
        $configuration = [
            'numberOfFilesInFileBrowser' => $value,
        ];

        $extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
        $extensionConfigurationMock
            ->method('get')
            ->with('bynder2')
            ->willReturn($configuration);

        $extConf = ExtConf::create($extensionConfigurationMock);

        self::assertSame(
            $expected,
            $extConf->getNumberOfFilesInFileBrowser()
        );
    }
}
