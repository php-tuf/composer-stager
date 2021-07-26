<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Infrastructure\Process\FileCopier;

use PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\FileCopierFactory;
use PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\RsyncFileCopierInterface;
use PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\SymfonyFileCopierInterface;
use PhpTuf\ComposerStager\Tests\Unit\TestCase;
use Prophecy\Argument;
use Symfony\Component\Process\ExecutableFinder;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\FileCopierFactory
 * @covers ::__construct
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\RsyncFileCopier|\Prophecy\Prophecy\ObjectProphecy rsyncFileCopier
 * @property \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\SymfonyFileCopierInterface|\Prophecy\Prophecy\ObjectProphecy symfonyFileCopier
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
        $this->rsyncFileCopier = $this->prophesize(RsyncFileCopierInterface::class);
        $this->symfonyFileCopier = $this->prophesize(SymfonyFileCopierInterface::class);
    }

    private function createSut(): FileCopierFactory
    {
        $executableFinder = $this->executableFinder->reveal();
        $rsyncFileCopier = $this->rsyncFileCopier->reveal();
        $symfonyFileCopier = $this->symfonyFileCopier->reveal();
        return new FileCopierFactory($executableFinder, $rsyncFileCopier, $symfonyFileCopier);
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
                'instanceOf' => SymfonyFileCopierInterface::class,
            ],
        ];
    }
}
