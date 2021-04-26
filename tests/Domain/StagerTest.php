<?php

namespace PhpTuf\ComposerStager\Tests\Domain;

use PhpTuf\ComposerStager\Domain\Stager;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\DirectoryNotWritableException;
use PhpTuf\ComposerStager\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Exception\LogicException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Filesystem\Filesystem;
use PhpTuf\ComposerStager\Process\ProcessFactory;
use PhpTuf\ComposerStager\Tests\TestCase;
use Prophecy\Argument;
use Symfony\Component\Process\Process;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Stager
 * @covers ::__construct
 * @covers ::runCommand
 * @covers ::stage
 * @covers ::validate
 * @covers ::validateCommand
 * @covers ::validatePreconditions
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 *
 * @property \PhpTuf\ComposerStager\Filesystem\Filesystem|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Process\ProcessFactory|\Prophecy\Prophecy\ObjectProphecy $processFactory
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Process\Process $process
 */
class StagerTest extends TestCase
{
    private const STAGING_DIR = '/lorem/ipsum';
    private const INERT_COMMAND = 'about';

    protected function setUp(): void
    {
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
        $filesystem = $this->filesystem->reveal();
        $processFactory = $this->processFactory->reveal();
        return new Stager($filesystem, $processFactory);
    }

    /**
     * @dataProvider providerSuccess
     */
    public function testSuccess($givenCommand, $expectedCommand): void
    {
        $process = $this->process;
        $this->process
            ->mustRun()
            ->shouldBeCalledOnce();
        $this->processFactory
            ->create($expectedCommand)
            ->shouldBeCalledOnce()
            ->willReturn($process);
        $sut = $this->createSut();

        $sut->stage($givenCommand, static::STAGING_DIR);
    }

    public function providerSuccess(): array
    {
        return [
            [
                'givenCommand' => ['update'],
                'expectedCommand' => [
                    'composer',
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
                'expectedCommand' => [
                    'composer',
                    '--working-dir=' . self::STAGING_DIR,
                    'require',
                    'lorem/ipsum',
                    '--dry-run',
                ],
            ],
        ];
    }

    public function testStagingDirectoryDoesNotExist(): void
    {
        $this->expectException(DirectoryNotFoundException::class);
        $this->expectDeprecationMessageMatches('/staging directory/');
        $this->expectDeprecationMessageMatches('/exist/');

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
        $this->expectDeprecationMessageMatches('/staging directory/');
        $this->expectDeprecationMessageMatches('/writable/');

        $this->filesystem
            ->isWritable(static::STAGING_DIR)
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $sut = $this->createSut();

        $sut->stage([static::INERT_COMMAND], static::STAGING_DIR);
    }

    public function testEmptyCommand(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/empty/');

        $sut = $this->createSut();

        $sut->stage([], static::STAGING_DIR);
    }

    /**
     * @dataProvider providerCommandContainsWorkingDirOption
     */
    public function testCommandContainsWorkingDirOption($command): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/--working-dir/');
        $this->expectExceptionMessageMatches('/-d/');

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
            ->mustRun()
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
            ->mustRun()
            ->willThrow($exception);

        $sut = $this->createSut();

        $sut->stage([static::INERT_COMMAND], static::STAGING_DIR);
    }
}
