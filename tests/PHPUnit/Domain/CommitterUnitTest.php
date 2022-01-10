<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Domain;

use PhpTuf\ComposerStager\Domain\Committer;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\DirectoryNotWritableException;
use PhpTuf\ComposerStager\Exception\IOException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Committer
 * @covers \PhpTuf\ComposerStager\Domain\Committer::__construct
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath
 *
 * @property \PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy filesystem
 * @property \PhpTuf\ComposerStager\Domain\FileSyncer\FileSyncerInterface|\Prophecy\Prophecy\ObjectProphecy fileSyncer
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface stagingDir
 */
class CommitterUnitTest extends TestCase
{
    protected function setUp(): void
    {
        $this->activeDir = PathFactory::create(self::ACTIVE_DIR);
        $this->stagingDir = PathFactory::create(self::STAGING_DIR);
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
                $this->stagingDir,
                $this->activeDir,
                [],
                null,
                120
            )
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->commit($this->stagingDir, $this->activeDir);
    }

    /**
     * @covers ::commit
     *
     * @dataProvider providerCommitWithOptionalParams
     */
    public function testCommitWithOptionalParams($stagingDir, $activeDir, $givenExclusions, $expectedExclusions, $callback, $timeout): void
    {
        $stagingDir = PathFactory::create(self::STAGING_DIR);
        $activeDir = PathFactory::create(self::ACTIVE_DIR);

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
                'stagingDir' => '/one/two',
                'activeDir' => '/three/four',
                'givenExclusions' => [],
                'expectedExclusions' => [],
                'callback' => null,
                'timeout' => null,
            ],
            [
                'stagingDir' => 'five/six',
                'activeDir' => 'seven/eight',
                'givenExclusions' => ['/nine/ten'],
                'expectedExclusions' => ['/nine/ten'],
                'callback' => new TestOutputCallback(),
                'timeout' => 10,
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
        $stagingDir = PathFactory::create($stagingDir);
        $activeDir = PathFactory::create($activeDir);
        $missingDir = PathFactory::create($missingDir);

        $this->expectException(DirectoryNotFoundException::class);
        $this->expectExceptionMessageMatches($exceptionMessage);
        $this->filesystem
            ->exists($missingDir->getResolved())
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
                'stagingDir' => '/one/two/staging',
                'activeDir' => '/three/four/active',
                'missingDir' => '/three/four/active',
                'exceptionMessage' => '@active directory.*not exist.*/active@',
            ],
            [
                'stagingDir' => 'five/six/staging',
                'activeDir' => 'seven/eight/active',
                'missingDir' => 'five/six/staging',
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
        $activeDir = PathFactory::create($activeDir);

        $this->expectException(DirectoryNotWritableException::class);
        $this->expectExceptionMessageMatches(
            sprintf(
                '@active directory.*not writable.*%s@',
                addslashes($activeDir->getResolved())
            )
        );
        $this->filesystem
            ->isWritable($activeDir->getResolved())
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
            ['activeDir' => '/one/two'],
            ['activeDir' => '/three/four'],
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
            ->exists(static::STAGING_DIR)
            ->shouldBeCalledOnce()
            ->willReturn($expected);
        $sut = $this->createSut();

        $actual = $sut->directoryExists(static::STAGING_DIR);

        self::assertSame($expected, $actual, 'Correctly detected existence of staging directory.');
    }

    public function providerDirectoryExists(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @covers ::commit
     */
    public function testIOError(): void
    {
        $this->expectException(ProcessFailedException::class);

        $this->fileSyncer
            ->sync(Argument::cetera())
            ->shouldBeCalledOnce()
            ->willThrow(IOException::class);
        $sut = $this->createSut();

        $sut->commit($this->stagingDir, $this->activeDir);
    }
}
