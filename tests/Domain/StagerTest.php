<?php

namespace PhpTuf\ComposerStager\Tests\Domain;

use PhpTuf\ComposerStager\Domain\Stager;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\DirectoryNotWritableException;
use PhpTuf\ComposerStager\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Exception\LogicException;
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
 * @covers ::validateCommand
 * @covers ::validatePreconditions
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 *
 * @property \PhpTuf\ComposerStager\Filesystem\Filesystem|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Process\ProcessFactory|\Prophecy\Prophecy\ObjectProphecy $processFactory
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
        $process = $this->prophesize(Process::class);
        $this->processFactory = $this->prophesize(ProcessFactory::class);
        $this->processFactory
            ->create(Argument::any())
            ->willReturn($process);
    }

    private function createSut(): Stager
    {
        $filesystem = $this->filesystem->reveal();
        $processFactory = $this->processFactory->reveal();
        return new Stager($filesystem, $processFactory);
    }

    public function testSuccess(): void
    {
        $this->processFactory
            ->create([
                'composer',
                sprintf('--working-dir=%s', self::STAGING_DIR),
                static::INERT_COMMAND,
            ])
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->stage([static::INERT_COMMAND], static::STAGING_DIR);

        self::assertTrue(true, 'Completed correctly.');
    }

    public function testMissingStagingDirectory(): void
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
}
