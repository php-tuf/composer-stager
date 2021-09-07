<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Infrastructure\FileSyncer;

use PhpTuf\ComposerStager\Infrastructure\FileSyncer\FileSyncerFactory;
use PhpTuf\ComposerStager\Infrastructure\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Tests\Unit\TestCase;
use Prophecy\Argument;
use Symfony\Component\Process\ExecutableFinder;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\FileSyncer\FileSyncerFactory
 * @covers ::__construct
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\FileSyncer\FileSyncerInterface|\Prophecy\Prophecy\ObjectProphecy phpFileSyncer
 * @property \PhpTuf\ComposerStager\Infrastructure\FileSyncer\FileSyncerInterface|\Prophecy\Prophecy\ObjectProphecy rsyncFileSyncer
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Process\ExecutableFinder executableFinder
 */
class FileSyncerFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->executableFinder = $this->prophesize(ExecutableFinder::class);
        $this->executableFinder
            ->find(Argument::any())
            ->willReturn(null);
        $this->phpFileSyncer = $this->prophesize(FileSyncerInterface::class);
        $this->rsyncFileSyncer = $this->prophesize(FileSyncerInterface::class);
    }

    private function createSut(): FileSyncerFactory
    {
        $executableFinder = $this->executableFinder->reveal();
        $phpFileSyncer = $this->phpFileSyncer->reveal();
        $rsyncFileSyncer = $this->rsyncFileSyncer->reveal();
        return new FileSyncerFactory($executableFinder, $phpFileSyncer, $rsyncFileSyncer);
    }

    /**
     * @covers ::create
     *
     * @dataProvider providerCreate
     */
    public function testCreate($executable, $calledTimes, $path, $instanceOf): void
    {
        $this->executableFinder
            ->find($executable)
            ->shouldBeCalledTimes($calledTimes)
            ->willReturn($path);
        $sut = $this->createSut();

        $fileSyncer = $sut->create();

        /** @noinspection UnnecessaryAssertionInspection */
        self::assertInstanceOf($instanceOf, $fileSyncer, 'Returned correct file syncer.');
    }

    public function providerCreate(): array
    {
        return [
            [
                'executable' => 'rsync',
                'calledTimes' => 1,
                'path' => '/usr/bin/rsync',
                'instanceOf' => FileSyncerInterface::class,
            ],
            [
                'executable' => 'n/a',
                'calledTimes' => 0,
                'path' => null,
                'instanceOf' => FileSyncerInterface::class,
            ],
        ];
    }
}
