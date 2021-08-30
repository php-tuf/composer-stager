<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Domain;

use PhpTuf\ComposerStager\Domain\Committer;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\DirectoryNotWritableException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Infrastructure\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Tests\Unit\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Committer
 * @covers \PhpTuf\ComposerStager\Domain\Committer::__construct
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy filesystem
 * @property \PhpTuf\ComposerStager\Infrastructure\FileSyncer\FileSyncerInterface|\Prophecy\Prophecy\ObjectProphecy fileSyncer
 */
class CommitterTest extends TestCase
{
    protected function setUp(): void
    {
        $this->fileSyncer = $this->prophesize(FileSyncerInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->exists(Argument::any())
            ->willReturn(true);
        $this->filesystem
            ->isWritable(Argument::any())
            ->willReturn(true);
    }

    protected function createSut(): Committer
    {
        $fileSyncer = $this->fileSyncer->reveal();
        $filesystem = $this->filesystem->reveal();
        return new Committer($fileSyncer, $filesystem);
    }

    /**
     * @covers ::commit
     */
    public function testCommitWithMinimumParams(): void
    {
        $this->fileSyncer
            ->sync(
                self::STAGING_DIR_DEFAULT,
                self::ACTIVE_DIR_DEFAULT,
                [self::STAGING_DIR_DEFAULT],
                null,
                120
            )
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->commit(self::STAGING_DIR_DEFAULT, self::ACTIVE_DIR_DEFAULT);
    }

    /**
     * @covers ::commit
     *
     * @dataProvider providerCommitWithOptionalParams
     */
    public function testCommitWithOptionalParams($stagingDir, $activeDir, $givenExclusions, $expectedExclusions, $callback, $timeout): void
    {
        $this->fileSyncer
            ->sync($stagingDir, $activeDir, $expectedExclusions, $callback, $timeout)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->commit($stagingDir, $activeDir, $givenExclusions, $callback, $timeout);
    }

    public function providerCommitWithOptionalParams(): array
    {
        return [
            [
                'stagingDir' => '/lorem/ipsum',
                'activeDir' => '/dolor/sit',
                'givenExclusions' => null,
                'expectedExclusions' => ['/lorem/ipsum'],
                'callback' => null,
                'timeout' => null,
            ],
            [
                'stagingDir' => 'amet/consectetur',
                'activeDir' => 'adipiscing/elit',
                'givenExclusions' => ['/sed/do'],
                'expectedExclusions' => [
                    '/sed/do',
                    'amet/consectetur',
                ],
                'callback' => new TestProcessOutputCallback(),
                'timeout' => 10,
            ],
            [
                'stagingDir' => '/do/eiusmod',
                'activeDir' => '/tempor/incididunt',
                'givenExclusions' => ['/do/eiusmod'],
                'expectedExclusions' => ['/do/eiusmod'],
                'callback' => null,
                'timeout' => null,
            ],
        ];
    }

    /**
     * @covers ::commit
     *
     * @dataProvider providerDirectoryNotFound
     */
    public function testDirectoryNotFound($stagingDir, $activeDir, $missingDir, $exceptionMessage): void
    {
        $this->expectException(DirectoryNotFoundException::class);
        $this->expectExceptionMessageMatches($exceptionMessage);
        $this->filesystem
            ->exists($missingDir)
            ->willReturn(false);
        $this->fileSyncer
            ->sync(Argument::cetera())
            ->shouldNotBeCalled();
        $sut = $this->createSut();

        $sut->commit($stagingDir, $activeDir);
    }

    public function providerDirectoryNotFound(): array
    {
        return [
            [
                'stagingDir' => '/lorem/ipsum/staging',
                'activeDir' => '/dolor/sit/active',
                'missingDir' => '/dolor/sit/active',
                'exceptionMessage' => '@active directory.*not exist.*/active@',
            ],
            [
                'stagingDir' => 'amet/consectetur/staging',
                'activeDir' => 'adipiscing/elit/active',
                'missingDir' => 'amet/consectetur/staging',
                'exceptionMessage' => '@staging directory.*not exist.*staging@',
            ],
        ];
    }

    /**
     * @covers ::commit
     *
     * @dataProvider providerActiveDirectoryNotWritable
     */
    public function testActiveDirectoryNotWritable($activeDir): void
    {
        $this->expectException(DirectoryNotWritableException::class);
        $this->expectExceptionMessageMatches(sprintf('@active directory.*not writable.*%s@', addslashes($activeDir)));
        $this->filesystem
            ->isWritable($activeDir)
            ->willReturn(false);
        $this->fileSyncer
            ->sync(Argument::cetera())
            ->shouldNotBeCalled();
        $sut = $this->createSut();

        $sut->commit($activeDir, $activeDir);
    }

    public function providerActiveDirectoryNotWritable(): array
    {
        return [
            ['activeDir' => '/lorem/ipsum'],
            ['activeDir' => '/dolor/sit'],
        ];
    }

    /**
     * @covers ::directoryExists
     *
     * @dataProvider providerDirectoryExists
     */
    public function testDirectoryExists($expected): void
    {
        $this->filesystem
            ->exists(static::STAGING_DIR_DEFAULT)
            ->shouldBeCalledOnce()
            ->willReturn($expected);
        $sut = $this->createSut();

        $actual = $sut->directoryExists(static::STAGING_DIR_DEFAULT);

        self::assertSame($expected, $actual, 'Correctly detected existence of staging directory.');
    }

    public function providerDirectoryExists(): array
    {
        return [
            [true],
            [false],
        ];
    }
}
