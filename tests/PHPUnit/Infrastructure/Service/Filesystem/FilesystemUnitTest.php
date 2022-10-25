<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\Filesystem;

use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Exception\LogicException;
use PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem;
use PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Service\ProcessOutputCallback\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Value\Path\TestPath;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Prophecy\Argument;
use Symfony\Component\Filesystem\Exception\FileNotFoundException as SymfonyFileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException as SymfonyIOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem
 *
 * @covers \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem::__construct
 *
 * @property \PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Value\Path\TestPath $activeDir
 * @property \PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Value\Path\TestPath $stagingDir
 * @property \Symfony\Component\Filesystem\Filesystem|\Prophecy\Prophecy\ObjectProphecy $symfonyFilesystem
 */
final class FilesystemUnitTest extends TestCase
{
    protected function setUp(): void
    {
        $this->activeDir = new TestPath(self::ACTIVE_DIR);
        $this->stagingDir = new TestPath(self::STAGING_DIR);
        $this->symfonyFilesystem = $this->prophesize(SymfonyFilesystem::class);
    }

    protected function createSut(): Filesystem
    {
        $symfonyFilesystem = $this->symfonyFilesystem->reveal();

        return new Filesystem($symfonyFilesystem);
    }

    /**
     * @covers ::copy
     *
     * @dataProvider providerCopy
     */
    public function testCopy(string $source, string $destination): void
    {
        $source = new TestPath($source);
        $destination = new TestPath($destination);
        $this->symfonyFilesystem
            ->copy($source->resolve(), $destination->resolve(), true)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->copy($source, $destination);
    }

    public function providerCopy(): array
    {
        return [
            [
                'source' => 'one',
                'destination' => 'two',
            ],
            [
                'source' => 'three',
                'destination' => 'four',
            ],
        ];
    }

    /** @covers ::copy */
    public function testCopyFailure(): void
    {
        $this->expectException(IOException::class);

        /** @noinspection PhpParamsInspection */
        $this->symfonyFilesystem
            ->copy(Argument::cetera())
            ->willThrow(SymfonyIOException::class);
        $sut = $this->createSut();

        $sut->copy($this->activeDir, $this->stagingDir);
    }

    /** @covers ::copy */
    public function testCopySourceDirectoryNotFound(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('The source file does not exist or is not a file at "%s"', $this->activeDir->resolve()));

        /** @noinspection PhpParamsInspection */
        $this->symfonyFilesystem
            ->copy(Argument::cetera())
            ->willThrow(SymfonyFileNotFoundException::class);
        $sut = $this->createSut();

        $sut->copy($this->activeDir, $this->stagingDir);
    }

    /** @covers ::copy */
    public function testCopyDirectoriesTheSame(): void
    {
        $source = new TestPath('same');
        $destination = $source;

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('The source and destination files cannot be the same at "%s"', $source->resolve()));

        $sut = $this->createSut();

        $sut->copy($source, $destination);
    }

    /**
     * @covers ::exists
     *
     * @dataProvider providerExists
     */
    public function testExists(string $path, bool $expected): void
    {
        $stagingDir = new TestPath($path);
        $this->symfonyFilesystem
            ->exists($path)
            ->shouldBeCalledOnce()
            ->willReturn($expected);
        $sut = $this->createSut();

        $actual = $sut->exists($stagingDir);

        self::assertEquals($expected, $actual, 'Correctly detected existence of path.');
    }

    public function providerExists(): array
    {
        return [
            [
                'path' => '/one/two',
                'expected' => true,
            ],
            [
                'path' => 'three/four',
                'expected' => false,
            ],
        ];
    }

    /**
     * @covers ::mkdir
     *
     * @dataProvider providerMkdir
     */
    public function testMkdir(string $dir): void
    {
        $stagingDir = new TestPath($dir);
        $this->symfonyFilesystem
            ->mkdir($dir)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->mkdir($stagingDir);
    }

    public function providerMkdir(): array
    {
        return [
            ['dir' => 'one'],
            ['dir' => 'two'],
        ];
    }

    /** @covers ::mkdir */
    public function testMkdirFailure(): void
    {
        $this->expectException(IOException::class);

        $this->symfonyFilesystem
            ->mkdir(Argument::any())
            ->willThrow(SymfonyIOException::class);
        $sut = $this->createSut();

        $sut->mkdir($this->stagingDir);
    }

    /**
     * @covers ::remove
     *
     * @dataProvider providerRemove
     */
    public function testRemove(
        string $path,
        ?ProcessOutputCallbackInterface $callback,
        ?int $givenTimeout,
        int $expectedTimeout
    ): void {
        $stagingDir = new TestPath($path);
        $this->symfonyFilesystem
            ->remove($path)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->remove($stagingDir, $callback, $givenTimeout);

        self::assertSame((string) $expectedTimeout, ini_get('max_execution_time'), 'Correctly set process timeout.');
    }

    public function providerRemove(): array
    {
        return [
            [
                'path' => '/one/two',
                'callback' => null,
                'givenTimeout' => null,
                'expectedTimeout' => 0,
            ],
            [
                'path' => 'three/four',
                'callback' => new TestProcessOutputCallback(),
                'givenTimeout' => 10,
                'expectedTimeout' => 10,
            ],
        ];
    }

    /** @covers ::remove */
    public function testRemoveException(): void
    {
        $this->expectException(IOException::class);

        $this->symfonyFilesystem
            ->remove(Argument::any())
            ->willThrow(SymfonyIOException::class);
        $sut = $this->createSut();

        $sut->remove($this->stagingDir);
    }
}
