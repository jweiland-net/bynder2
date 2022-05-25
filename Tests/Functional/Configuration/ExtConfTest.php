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
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * Test case.
 */
class ExtConfTest extends FunctionalTestCase
{
    /**
     * @var ExtConf
     */
    protected $subject;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/bynder2'
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new ExtConf(new ExtensionConfiguration());
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject
        );

        parent::tearDown();
    }

    public function numberOfFilesDataProvider(): array
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

    /**
     * @test
     */
    public function getNumberOfFilesInFileBrowserInitiallyReturns100(): void
    {
        $this->assertSame(
            100,
            $this->subject->getNumberOfFilesInFileBrowser()
        );
    }

    /**
     * @test
     *
     * @dataProvider numberOfFilesDataProvider
     */
    public function setNumberOfFilesInFileBrowserWithStringResultsInInteger($value, $expected): void
    {
        $this->subject->setNumberOfFilesInFileBrowser($value);

        $this->assertSame(
            $expected,
            $this->subject->getNumberOfFilesInFileBrowser()
        );
    }
}
