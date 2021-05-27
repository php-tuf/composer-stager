<?php

namespace PhpTuf\ComposerStager\Tests\Domain;

use PhpTuf\ComposerStager\Domain\Stager;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\DirectoryNotWritableException;
use PhpTuf\ComposerStager\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Exception\LogicException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem;
use PhpTuf\ComposerStager\Infrastructure\Process\ComposerRunner;
use PhpTuf\ComposerStager\Tests\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Stager
 * @covers \PhpTuf\ComposerStager\Domain\Stager
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 * @uses \PhpTuf\ComposerStager\Exception\ProcessFailedException
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Infrastructure\Process\ComposerRunner|\Prophecy\Prophecy\ObjectProphecy $composerRunner
 */
class StagerTest extends TestCase
{
    private const STAGING_DIR = '/lorem/ipsum';
    private const INERT_COMMAND = 'about';

    protected function setUp(): void
    {
        $this->composerRunner = $this->prophesize(ComposerRunner::class);
        $this->filesystem = $this->prophesize(Filesystem::class);
        $this->filesystem
            ->exists(static::STAGING_DIR)
            ->willReturn(true);
        $this->filesystem
            ->isWritable(static::STAGING_DIR)
            ->willReturn(true);
    }

    private function createSut(): Stager
    {
        $composerRunner = $this->composerRunner->reveal();
        $filesystem = $this->filesystem->reveal();
        return new Stager($composerRunner, $filesystem);
    }

    /**
     * @dataProvider providerHappyPath
     */
    public function testHappyPath($givenCommand, $expectedCommand, $callback): void
    {
        $this->composerRunner
            ->run($expectedCommand, $callback)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->stage($givenCommand, static::STAGING_DIR, $callback);
    }

    public function providerHappyPath(): array
    {
        return [
            [
                'givenCommand' => ['update'],
                'expectedCommand' => [
                    '--working-dir=' . self::STAGING_DIR,
                    'update',
                ],
                'callback' => null,
            ],
            [
                'givenCommand' => [static::INERT_COMMAND],
                'expectedCommand' => [
                    '--working-dir=' . self::STAGING_DIR,
                    static::INERT_COMMAND,
                ],
                'callback' => static function () {
                }
            ],
        ];
    }

    public function testStagingDirectoryDoesNotExist(): void
    {
        $this->expectException(DirectoryNotFoundException::class);
        $this->expectExceptionMessageMatches('/staging directory.*not exist/');

        $this->filesystem
            ->exists(static::STAGING_DIR)
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $sut = $this->createSut();

        $sut->stage([static::INERT_COMMAND], static::STAGING_DIR);
    }

    public function testStagingDirectoryNotWritable(): void
    {
        $this->expectException(DirectoryNotWritableException::class);
        $this->expectExceptionMessageMatches('/staging directory.*not writable/');

        $this->filesystem
            ->isWritable(static::STAGING_DIR)
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $sut = $this->createSut();

        $sut->stage([static::INERT_COMMAND], static::STAGING_DIR);
    }

    public function testEmptyCommand(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/empty/');

        $sut = $this->createSut();

        $sut->stage([], static::STAGING_DIR);
    }

    public function testCommandContainsComposer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/cannot begin/');

        $sut = $this->createSut();

        $sut->stage([
            'composer',
            static::INERT_COMMAND,
        ], static::STAGING_DIR);
    }

    /**
     * @dataProvider providerCommandContainsWorkingDirOption
     */
    public function testCommandContainsWorkingDirOption($command): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/--working-dir/');

        $sut = $this->createSut();

        $sut->stage($command, static::STAGING_DIR);
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

        $sut->stage([static::INERT_COMMAND], static::STAGING_DIR);
    }

    public function providerProcessExceptions(): array
    {
        return [
            [
                'exception' => new LogicException('lorem'),
                'message' => 'lorem',
            ],
            [
                'exception' => new ProcessFailedException('ipsum'),
                'message' => 'ipsum',
            ],
        ];
    }
}
