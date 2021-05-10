<?php

namespace PhpTuf\ComposerStager\Tests\Domain;

use PhpTuf\ComposerStager\Domain\Stager;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\DirectoryNotWritableException;
use PhpTuf\ComposerStager\Exception\FileNotFoundException;
use PhpTuf\ComposerStager\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem;
use PhpTuf\ComposerStager\Infrastructure\Process\ComposerFinder;
use PhpTuf\ComposerStager\Infrastructure\Process\ProcessFactory;
use PhpTuf\ComposerStager\Tests\TestCase;
use Prophecy\Argument;
use Symfony\Component\Process\Process;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Stager
 * @covers \PhpTuf\ComposerStager\Domain\Stager
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 * @uses \PhpTuf\ComposerStager\Exception\ProcessFailedException
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Infrastructure\Process\ComposerFinder|\Prophecy\Prophecy\ObjectProphecy $composerFinder
 * @property \PhpTuf\ComposerStager\Infrastructure\Process\ProcessFactory|\Prophecy\Prophecy\ObjectProphecy $processFactory
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Process\Process $process
 */
class StagerTest extends TestCase
{
    private const STAGING_DIR = '/lorem/ipsum';
    private const INERT_COMMAND = 'about';

    protected function setUp(): void
    {
        $this->composerFinder = $this->prophesize(ComposerFinder::class);
        $this->composerFinder
            ->find()
            ->willReturn('composer');
        $this->filesystem = $this->prophesize(Filesystem::class);
        $this->filesystem
            ->exists(static::STAGING_DIR)
            ->willReturn(true);
        $this->filesystem
            ->isWritable(static::STAGING_DIR)
            ->willReturn(true);
        $this->process = $this->prophesize(Process::class);
        $this->processFactory = $this->prophesize(ProcessFactory::class);
        $this->processFactory
            ->create(Argument::any())
            ->willReturn($this->process);
    }

    private function createSut(): Stager
    {
        $composerFinder = $this->composerFinder->reveal();
        $filesystem = $this->filesystem->reveal();
        $processFactory = $this->processFactory->reveal();
        return new Stager($composerFinder, $filesystem, $processFactory);
    }

    /**
     * @dataProvider providerHappyPathNoCallback
     */
    public function testHappyPathNoCallback($givenCommand, $composerPath, $expectedCommand): void
    {
        $this->composerFinder
            ->find()
            ->shouldBeCalledOnce()
            ->willReturn($composerPath);
        $process = $this->process;
        $this->process
            ->mustRun(null)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal());
        $this->processFactory
            ->create($expectedCommand)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal());
        $sut = $this->createSut();

        $sut->stage($givenCommand, static::STAGING_DIR);
    }

    public function providerHappyPathNoCallback(): array
    {
        return [
            [
                'givenCommand' => ['update'],
                'composerPath' => '/lorem/composer',
                'expectedCommand' => [
                    '/lorem/composer',
                    '--working-dir=' . self::STAGING_DIR,
                    'update',
                ],
            ],
            [
                'givenCommand' => [
                    'require',
                    'lorem/ipsum',
                    '--dry-run',
                ],
                'composerPath' => '/ipsum/composer',
                'expectedCommand' => [
                    '/ipsum/composer',
                    '--working-dir=' . self::STAGING_DIR,
                    'require',
                    'lorem/ipsum',
                    '--dry-run',
                ],
            ],
        ];
    }

    public function testHappyPathWithCallback(): void
    {
        $callback = static function (): void {
        };

        $process = $this->process;
        $this->process
            ->mustRun($callback)
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal());
        $this->processFactory
            ->create(Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal());
        $sut = $this->createSut();

        $sut->stage([static::INERT_COMMAND], static::STAGING_DIR, $callback);
    }

    public function testStagingDirectoryDoesNotExist(): void
    {
        $this->expectException(DirectoryNotFoundException::class);
        $this->expectExceptionMessageMatches('/staging directory/');
        $this->expectExceptionMessageMatches('/exist/');

        $this->filesystem
            ->exists(static::STAGING_DIR)
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $sut = $this->createSut();

        $sut->stage([static::INERT_COMMAND], static::STAGING_DIR);
    }

    public function testNonWritableStagingDirectory(): void
    {
        $this->expectException(DirectoryNotWritableException::class);
        $this->expectExceptionMessageMatches('/staging directory/');
        $this->expectExceptionMessageMatches('/writable/');

        $this->filesystem
            ->isWritable(static::STAGING_DIR)
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $sut = $this->createSut();

        $sut->stage([static::INERT_COMMAND], static::STAGING_DIR);
    }

    public function testComposerExecutableNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);

        $this->composerFinder
            ->find()
            ->willThrow(FileNotFoundException::class);
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

    public function testProcessFailed(): void
    {
        $this->expectException(ProcessFailedException::class);

        $this->process
            ->isSuccessful()
            ->willReturn(false);
        $exception = $this->prophesize(\Symfony\Component\Process\Exception\ProcessFailedException::class);
        $exception = $exception->reveal();
        $this->process
            ->mustRun(Argument::cetera())
            ->willThrow($exception);

        $sut = $this->createSut();

        $sut->stage([static::INERT_COMMAND], static::STAGING_DIR);
    }

    public function testProcessFailedException(): void
    {
        $this->expectException(ProcessFailedException::class);

        $this->process
            ->isSuccessful()
            ->willReturn(false);
        $exception = $this->prophesize(\Symfony\Component\Process\Exception\ProcessFailedException::class);
        $exception = $exception->reveal();
        $this->process
            ->mustRun(Argument::cetera())
            ->willThrow($exception);

        $sut = $this->createSut();

        $sut->stage([static::INERT_COMMAND], static::STAGING_DIR);
    }
}
