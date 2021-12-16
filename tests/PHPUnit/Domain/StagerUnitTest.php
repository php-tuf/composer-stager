<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Domain;

use PhpTuf\ComposerStager\Domain\Stager;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\DirectoryNotWritableException;
use PhpTuf\ComposerStager\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Exception\IOException;
use PhpTuf\ComposerStager\Exception\LogicException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Process\Runner\ComposerRunnerInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Stager
 * @covers \PhpTuf\ComposerStager\Domain\Stager
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 * @uses \PhpTuf\ComposerStager\Exception\ProcessFailedException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath
 *
 * @property \PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy filesystem
 * @property \PhpTuf\ComposerStager\Domain\Process\Runner\ComposerRunnerInterface|\Prophecy\Prophecy\ObjectProphecy composerRunner
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface stagingDir
 */
class StagerUnitTest extends TestCase
{
    private const INERT_COMMAND = 'about';

    protected function setUp(): void
    {
        $this->stagingDir = PathFactory::create(self::STAGING_DIR);
        $this->composerRunner = $this->prophesize(ComposerRunnerInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->exists($this->stagingDir->getResolved())
            ->willReturn(true);
        $this->filesystem
            ->isWritable($this->stagingDir->getResolved())
            ->willReturn(true);
    }

    protected function createSut(): Stager
    {
        $composerRunner = $this->composerRunner->reveal();
        $filesystem = $this->filesystem->reveal();
        return new Stager($composerRunner, $filesystem);
    }

    /**
     * @dataProvider providerHappyPath
     */
    public function testHappyPath($givenCommand, $expectedCommand, $callback, $timeout): void
    {
        $this->composerRunner
            ->run($expectedCommand, $callback, $timeout)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->stage($givenCommand, $this->stagingDir, $callback, $timeout);
    }

    public function providerHappyPath(): array
    {
        return [
            [
                'givenCommand' => ['update'],
                'expectedCommand' => [
                    '--working-dir=' . PathFactory::create(self::STAGING_DIR)->getResolved(),
                    'update',
                ],
                'callback' => null,
                'timeout' => null,
            ],
            [
                'givenCommand' => [static::INERT_COMMAND],
                'expectedCommand' => [
                    '--working-dir=' . PathFactory::create(self::STAGING_DIR)->getResolved(),
                    static::INERT_COMMAND,
                ],
                'callback' => new TestOutputCallback(),
                'timeout' => 10,
            ],
        ];
    }

    public function testStagingDirectoryDoesNotExist(): void
    {
        $this->expectException(DirectoryNotFoundException::class);
        $this->expectExceptionMessageMatches('/staging directory.*not exist/');

        $this->filesystem
            ->exists($this->stagingDir->getResolved())
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $sut = $this->createSut();

        $sut->stage([static::INERT_COMMAND], $this->stagingDir);
    }

    public function testStagingDirectoryNotWritable(): void
    {
        $this->expectException(DirectoryNotWritableException::class);
        $this->expectExceptionMessageMatches('/staging directory.*not writable/');

        $this->filesystem
            ->isWritable($this->stagingDir->getResolved())
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $sut = $this->createSut();

        $sut->stage([static::INERT_COMMAND], $this->stagingDir);
    }

    public function testEmptyCommand(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/empty/');

        $sut = $this->createSut();

        $sut->stage([], $this->stagingDir);
    }

    public function testCommandContainsComposer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/cannot begin/');

        $sut = $this->createSut();

        $sut->stage([
            'composer',
            static::INERT_COMMAND,
        ], $this->stagingDir);
    }

    /**
     * @dataProvider providerCommandContainsWorkingDirOption
     */
    public function testCommandContainsWorkingDirOption($command): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/--working-dir/');

        $sut = $this->createSut();

        $sut->stage($command, $this->stagingDir);
    }

    public function providerCommandContainsWorkingDirOption(): array
    {
        return [
            [['--working-dir' => 'lorem/ipsum']],
            [['-d' => 'lorem/ipsum']],
        ];
    }

    /**
     * @dataProvider providerProcessExceptions
     */
    public function testProcessExceptions($exception, $message): void
    {
        $this->expectException(ProcessFailedException::class);
        $this->expectExceptionMessage($message);

        $this->composerRunner
            ->run(Argument::cetera())
            ->willThrow($exception);

        $sut = $this->createSut();

        $sut->stage([static::INERT_COMMAND], $this->stagingDir);
    }

    public function providerProcessExceptions(): array
    {
        return [
            [
                'exception' => new IOException('lorem'),
                'message' => 'lorem',
            ],
            [
                'exception' => new LogicException('ipsum'),
                'message' => 'ipsum',
            ],
            [
                'exception' => new ProcessFailedException('dolor'),
                'message' => 'dolor',
            ],
        ];
    }
}
