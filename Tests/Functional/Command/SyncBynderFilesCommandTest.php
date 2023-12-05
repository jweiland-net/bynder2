<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Tests\Functional\Command;

use JWeiland\Bynder2\Command\SyncBynderFilesCommand;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * Test case.
 */
class SyncBynderFilesCommandTest extends FunctionalTestCase
{
    use ProphecyTrait;

    /**
     * @var SyncBynderFilesCommand
     */
    protected $subject;

    /**
     * @var InputInterface|ObjectProphecy
     */
    protected $inputProphecy;

    /**
     * @var OutputInterface|ObjectProphecy
     */
    protected $outputProphecy;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/bynder2',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->inputProphecy = $this->prophesize(Input::class);
        $this->outputProphecy = $this->prophesize(Output::class);

        /** @var FrontendInterface|ObjectProphecy $cache */
        $cache = $this->prophesize(VariableFrontend::class);
        $cache
            ->flush()
            ->shouldBeCalled();

        // We have to use GeneralUtility:: makeInstance here because of injecting the logger
        $this->subject = GeneralUtility::makeInstance(SyncBynderFilesCommand::class);
        $this->subject->setFileInfoCache($cache->reveal());
        $this->subject->setPageNavCache($cache->reveal());
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject
        );

        parent::tearDown();
    }

    /**
     * @test
     */
    public function runWithoutBynderStorageWillReturn0(): void
    {
        $this->subject->setBynderStorages(new \SplObjectStorage());

        $this->outputProphecy
            ->writeln('Clear bynder caches')
            ->shouldBeCalled();

        $this->outputProphecy
            ->writeln('Start synchronizing bynder files')
            ->shouldBeCalled();

        $this->outputProphecy
            ->writeln('No bynder storages found.')
            ->shouldBeCalled();

        self::assertSame(
            0,
            $this->subject->run(
                $this->inputProphecy->reveal(),
                $this->outputProphecy->reveal()
            )
        );
    }

    /**
     * @test
     */
    public function runWillSyncStorage(): void
    {
        /** @var Folder $folder */
        $folder = $this->prophesize(Folder::class)->reveal();

        /** @var ResourceStorage|ObjectProphecy $resourceStorageProphecy */
        $resourceStorageProphecy = $this->prophesize(ResourceStorage::class);
        $resourceStorageProphecy
            ->getRootLevelFolder()
            ->shouldBeCalled()
            ->willReturn($folder);
        $resourceStorageProphecy
            ->countFilesInFolder($folder)
            ->shouldBeCalled()
            ->willReturn(12);
        $resourceStorageProphecy
            ->getUid()
            ->shouldBeCalled()
            ->willReturn(2);

        $bynderStorages = new \SplObjectStorage();
        $bynderStorages->attach($resourceStorageProphecy->reveal());

        $this->subject->setBynderStorages($bynderStorages);

        /** @var Indexer|ObjectProphecy $indexerProphecy */
        $indexerProphecy = $this->prophesize(Indexer::class);
        $indexerProphecy
            ->processChangesInStorages()
            ->shouldBeCalled();
        GeneralUtility::addInstance(Indexer::class, $indexerProphecy->reveal());

        self::assertSame(
            0,
            $this->subject->run(
                $this->inputProphecy->reveal(),
                $this->outputProphecy->reveal()
            )
        );
    }
}
