<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Filesystem\Service;

use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPath;
use PhpTuf\ComposerStager\Tests\Process\Service\TestOutputCallback;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Filesystem\Exception\FileNotFoundException as SymfonyFileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException as SymfonyIOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem
 *
 * @covers \PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem::__construct
 */
final class FilesystemUnitTest extends TestCase
{
    private PathFactoryInterface|ObjectProphecy $pathFactory;
    private SymfonyFilesystem|ObjectProphecy $symfonyFilesystem;

    protected function setUp(): void
    {
        $this->activeDir = new TestPath(PathHelper::activeDirRelative());
        $this->stagingDir = new TestPath(PathHelper::stagingDirRelative());
        $this->pathFactory = $this->prophesize(PathFactoryInterface::class);
        $this->symfonyFilesystem = $this->prophesize(SymfonyFilesystem::class);
    }

    private function createSut(): Filesystem
    {
        $pathFactory = $this->pathFactory->reveal();
        $symfonyFilesystem = $this->symfonyFilesystem->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new Filesystem($pathFactory, $symfonyFilesystem, $translatableFactory);
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
            ->copy($source->absolute(), $destination->absolute(), true)
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
        $previousMessage = 'Something went wrong';
        $message = sprintf('Failed to copy active-dir to staging-dir: %s', $previousMessage);
        $previous = new SymfonyIOException($previousMessage);
        $this->symfonyFilesystem
            ->copy(Argument::cetera())
            ->shouldBeCalled()
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut): void {
            $sut->copy($this->activeDir, $this->stagingDir);
        }, IOException::class, $message, $previous::class);
    }

    /** @covers ::copy */
    public function testCopySourceDirectoryNotFound(): void
    {
        $previous = SymfonyFileNotFoundException::class;
        $this->symfonyFilesystem
            ->copy(Argument::cetera())
            ->shouldBeCalled()
            ->willThrow($previous);
        $sut = $this->createSut();

        $message = sprintf('The source file does not exist or is not a file at %s', $this->activeDir->absolute());
        self::assertTranslatableException(function () use ($sut): void {
            $sut->copy($this->activeDir, $this->stagingDir);
        }, LogicException::class, $message, $previous);
    }

    /** @covers ::copy */
    public function testCopyDirectoriesTheSame(): void
    {
        $source = new TestPath('same');
        $destination = $source;
        $sut = $this->createSut();

        $message = sprintf('The source and destination files cannot be the same at %s', $source->absolute());
        self::assertTranslatableException(static function () use ($sut, $source, $destination): void {
            $sut->copy($source, $destination);
        }, LogicException::class, $message);
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
        $message = 'Failed to create directory at staging-dir';
        $previous = new SymfonyIOException($message);
        $this->symfonyFilesystem
            ->mkdir(Argument::any())
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut): void {
            $sut->mkdir($this->stagingDir);
        }, IOException::class, $message, $previous::class);
    }

    /**
     * @covers ::remove
     *
     * @dataProvider providerRemove
     */
    public function testRemove(
        string $path,
        ?OutputCallbackInterface $callback,
        ?int $givenTimeout,
        int $expectedTimeout,
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
                'callback' => new TestOutputCallback(),
                'givenTimeout' => 10,
                'expectedTimeout' => 10,
            ],
        ];
    }

    /** @covers ::remove */
    public function testRemoveException(): void
    {
        $message = 'Failed to remove directory.';
        $previous = new SymfonyIOException($message);
        $this->symfonyFilesystem
            ->remove(Argument::any())
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut): void {
            $sut->remove($this->stagingDir);
        }, IOException::class, $message, $previous::class);
    }
}
