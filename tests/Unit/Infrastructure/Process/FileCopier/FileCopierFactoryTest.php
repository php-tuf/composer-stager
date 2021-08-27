<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Infrastructure\Process\FileCopier;

use PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\FileCopierFactory;
use PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\RsyncFileCopierInterface;
use PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\PhpFileCopierInterface;
use PhpTuf\ComposerStager\Tests\Unit\TestCase;
use Prophecy\Argument;
use Symfony\Component\Process\ExecutableFinder;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\FileCopierFactory
 * @covers ::__construct
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\PhpFileCopierInterface|\Prophecy\Prophecy\ObjectProphecy phpFileCopier
 * @property \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\RsyncFileCopier|\Prophecy\Prophecy\ObjectProphecy rsyncFileCopier
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Process\ExecutableFinder executableFinder
 */
class FileCopierFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->executableFinder = $this->prophesize(ExecutableFinder::class);
        $this->executableFinder
            ->find(Argument::any())
            ->willReturn(null);
        $this->phpFileCopier = $this->prophesize(PhpFileCopierInterface::class);
        $this->rsyncFileCopier = $this->prophesize(RsyncFileCopierInterface::class);
    }

    private function createSut(): FileCopierFactory
    {
        $executableFinder = $this->executableFinder->reveal();
        $phpFileCopier = $this->phpFileCopier->reveal();
        $rsyncFileCopier = $this->rsyncFileCopier->reveal();
        return new FileCopierFactory($executableFinder, $phpFileCopier, $rsyncFileCopier);
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

        $fileCopier = $sut->create();

        self::assertInstanceOf($instanceOf, $fileCopier, 'Returned correct file copier.');
    }

    public function providerCreate(): array
    {
        return [
            [
                'executable' => 'rsync',
                'calledTimes' => 1,
                'path' => '/usr/bin/rsync',
                'instanceOf' => RsyncFileCopierInterface::class,
            ],
            [
                'executable' => 'n/a',
                'calledTimes' => 0,
                'path' => null,
                'instanceOf' => PhpFileCopierInterface::class,
            ],
        ];
    }
}
