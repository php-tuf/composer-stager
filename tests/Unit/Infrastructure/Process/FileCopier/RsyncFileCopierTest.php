<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Infrastructure\Process\FileCopier;

use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\IOException;
use PhpTuf\ComposerStager\Exception\LogicException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\RsyncFileCopier;
use PhpTuf\ComposerStager\Infrastructure\Process\Runner\RsyncRunnerInterface;
use PhpTuf\ComposerStager\Tests\Unit\Domain\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\Unit\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\RsyncFileCopier
 * @covers ::__construct
 * @covers ::copy
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Process\ExecutableFinder
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy filesystem
 * @property \PhpTuf\ComposerStager\Infrastructure\Process\Runner\RsyncRunnerInterface|\Prophecy\Prophecy\ObjectProphecy rsync
 */
class RsyncFileCopierTest extends TestCase
{
    public function setUp(): void
    {
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->exists(Argument::any())
            ->willReturn(true);
        $this->rsync = $this->prophesize(RsyncRunnerInterface::class);
    }

    protected function createSut(): RsyncFileCopier
    {
        $filesystem = $this->filesystem->reveal();
        $rsync = $this->rsync->reveal();
        return new RsyncFileCopier($filesystem, $rsync);
    }

    /**
     * @dataProvider providerCopy
     */
    public function testCopy($from, $to, $command, $callback): void
    {
        $this->rsync
            ->run($command, $callback)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->copy($from, $to, [], $callback);
    }

    public function providerCopy(): array
    {
        return [
            [
                'from' => 'lorem/ipsum',
                'to' => 'dolor/sit',
                'command' => [
                    '--recursive',
                    '--links',
                    '--verbose',
                    'lorem/ipsum' . DIRECTORY_SEPARATOR,
                    'dolor/sit',
                ],
                'callback' => null,
            ],
            [
                'from' => 'ipsum/lorem' . DIRECTORY_SEPARATOR,
                'to' => 'sit/dolor',
                'command' => [
                    '--recursive',
                    '--links',
                    '--verbose',
                    'ipsum/lorem' . DIRECTORY_SEPARATOR,
                    'sit/dolor',
                ],
                'callback' => new TestProcessOutputCallback(),
            ],
        ];
    }

    /**
     * @dataProvider providerCopyFailure
     */
    public function testCopyFailure($exception): void
    {
        $this->expectException(ProcessFailedException::class);

        $this->rsync
            ->run(Argument::cetera())
            ->willThrow($exception);
        $sut = $this->createSut();

        $sut->copy('lorem', 'ipsum', []);
    }

    public function providerCopyFailure(): array
    {
        return [
            [IOException::class],
            [LogicException::class],
            [ProcessFailedException::class],
        ];
    }

    public function testCopyFromDirectoryNotFound(): void
    {
        $this->expectException(DirectoryNotFoundException::class);

        $this->filesystem
            ->exists(Argument::any())
            ->willReturn(false);

        $sut = $this->createSut();

        $sut->copy(self::ACTIVE_DIR_DEFAULT, self::STAGING_DIR_DEFAULT);
    }
}
