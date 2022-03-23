<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Core\Beginner;

use PhpTuf\ComposerStager\Domain\Core\Beginner\Beginner;
use PhpTuf\ComposerStager\Domain\Exception\DirectoryAlreadyExistsException;
use PhpTuf\ComposerStager\Domain\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Domain\Service\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ProcessRunnerInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList;
use PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Service\ProcessOutputCallback\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Core\Beginner\Beginner
 *
 * @covers \PhpTuf\ComposerStager\Domain\Core\Beginner\Beginner::__construct
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\DirectoryAlreadyExistsException
 * @uses \PhpTuf\ComposerStager\Domain\Exception\DirectoryNotFoundException
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PathException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList
 *
 * @property \PhpTuf\ComposerStager\Domain\Service\FileSyncer\FileSyncerInterface|\Prophecy\Prophecy\ObjectProphecy $fileSyncer
 * @property \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $stagingDir
 */
class BeginnerUnitTest extends TestCase
{
    protected function setUp(): void
    {
        $this->activeDir = PathFactory::create(self::ACTIVE_DIR);
        $this->stagingDir = PathFactory::create(self::STAGING_DIR);
        $this->fileSyncer = $this->prophesize(FileSyncerInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->exists($this->activeDir->resolve())
            ->willReturn(true);
        $this->filesystem
            ->exists($this->stagingDir->resolve())
            ->willReturn(false);
    }

    protected function createSut(): Beginner
    {
        $fileSyncer = $this->fileSyncer->reveal();
        $filesystem = $this->filesystem->reveal();
        return new Beginner($fileSyncer, $filesystem);
    }

    /** @covers ::begin */
    public function testBeginWithMinimumParams(): void
    {
        $this->filesystem
            ->exists($this->activeDir->resolve())
            ->willReturn(true);
        $this->filesystem
            ->exists($this->stagingDir->resolve())
            ->willReturn(false);
        $this->fileSyncer
            ->sync($this->activeDir, $this->stagingDir, null, null, ProcessRunnerInterface::DEFAULT_TIMEOUT)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->begin($this->activeDir, $this->stagingDir);
    }

    /**
     * @covers ::begin
     *
     * @dataProvider providerBeginWithOptionalParams
     */
    public function testBeginWithOptionalParams($activeDir, $stagingDir, $exclusions, $callback, $timeout): void
    {
        $activeDir = PathFactory::create($activeDir);
        $stagingDir = PathFactory::create($stagingDir);

        $this->filesystem
            ->exists($activeDir->resolve())
            ->willReturn(true);
        $this->filesystem
            ->exists($stagingDir->resolve())
            ->willReturn(false);
        $this->fileSyncer
            ->sync($activeDir, $stagingDir, $exclusions, $callback, $timeout)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->begin($activeDir, $stagingDir, $exclusions, $callback, $timeout);
    }

    public function providerBeginWithOptionalParams(): array
    {
        return [
            [
                'activeDir' => 'one/two',
                'stagingDir' => 'three/four',
                'givenExclusions' => null,
                'callback' => null,
                'timeout' => null,
            ],
            [
                'activeDir' => 'five/six',
                'stagingDir' => 'seven/eight',
                'givenExclusions' => new PathList(['nine/ten']),
                'callback' => new TestProcessOutputCallback(),
                'timeout' => 100,
            ],
            [
                'activeDir' => 'eleven/twelve',
                'stagingDir' => 'thirteen/fourteen',
                'givenExclusions' => new PathList([
                    'thirteen/fourteen',
                    'fifteen/sixteen',
                ]),
                'callback' => null,
                'timeout' => null,
            ],
        ];
    }

    /** @covers ::begin */
    public function testBeginActiveDirectoryDoesNotExist(): void
    {
        $this->expectException(DirectoryNotFoundException::class);
        $this->expectExceptionMessageMatches('/active directory.*not exist/');

        $this->filesystem
            ->exists($this->activeDir->resolve())
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $sut = $this->createSut();

        $sut->begin($this->activeDir, $this->stagingDir);
    }

    /** @covers ::begin */
    public function testBeginStagingDirectoryAlreadyExists(): void
    {
        $this->expectException(DirectoryAlreadyExistsException::class);
        $this->expectExceptionMessageMatches('/staging directory already exists/');

        $this->filesystem
            ->exists($this->stagingDir->resolve())
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $sut = $this->createSut();

        $sut->begin($this->activeDir, $this->stagingDir);
    }

    /** @covers ::begin */
    public function testIOError(): void
    {
        $this->expectException(ProcessFailedException::class);

        $this->fileSyncer
            ->sync(Argument::cetera())
            ->shouldBeCalledOnce()
            ->willThrow(IOException::class);
        $sut = $this->createSut();

        $sut->begin($this->activeDir, $this->stagingDir);
    }
}
