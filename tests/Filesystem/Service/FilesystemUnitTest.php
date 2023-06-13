<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Filesystem\Service;

use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Exception\LogicException;
use PhpTuf\ComposerStager\Domain\ProcessOutputCallback\Service\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\Service\Filesystem;
use PhpTuf\ComposerStager\Infrastructure\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPath;
use PhpTuf\ComposerStager\Tests\ProcessOutputCallback\Service\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use Prophecy\Argument;
use Symfony\Component\Filesystem\Exception\FileNotFoundException as SymfonyFileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException as SymfonyIOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Filesystem\Service\Filesystem
 *
 * @covers \PhpTuf\ComposerStager\Infrastructure\Filesystem\Service\Filesystem::__construct
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\TranslatableExceptionTrait
 * @uses \PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableAwareTrait
 * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Value\TranslationParameters
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\Path\Factory\PathFactoryInterface|\Prophecy\Prophecy\ObjectProphecy $pathFactory
 * @property \PhpTuf\ComposerStager\Tests\Path\Value\TestPath $activeDir
 * @property \PhpTuf\ComposerStager\Tests\Path\Value\TestPath $stagingDir
 * @property \Symfony\Component\Filesystem\Filesystem|\Prophecy\Prophecy\ObjectProphecy $symfonyFilesystem
 */
final class FilesystemUnitTest extends TestCase
{
    protected function setUp(): void
    {
        $this->activeDir = new TestPath(self::ACTIVE_DIR);
        $this->stagingDir = new TestPath(self::STAGING_DIR);
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
            ->copy($source->resolved(), $destination->resolved(), true)
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
        $message = 'Failed to copy active-dir to staging-dir';
        $previous = new SymfonyIOException($message);
        $this->symfonyFilesystem
            ->copy(Argument::cetera())
            ->shouldBeCalled()
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut) {
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

        $message = sprintf('The source file does not exist or is not a file at %s', $this->activeDir->resolved());
        self::assertTranslatableException(function () use ($sut) {
            $sut->copy($this->activeDir, $this->stagingDir);
        }, LogicException::class, $message, $previous);
    }

    /** @covers ::copy */
    public function testCopyDirectoriesTheSame(): void
    {
        $source = new TestPath('same');
        $destination = $source;
        $sut = $this->createSut();

        $message = sprintf('The source and destination files cannot be the same at %s', $source->resolved());
        self::assertTranslatableException(static function () use ($sut, $source, $destination) {
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

        self::assertTranslatableException(function () use ($sut) {
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
        ?ProcessOutputCallbackInterface $callback,
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
                'callback' => new TestProcessOutputCallback(),
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

        self::assertTranslatableException(function () use ($sut) {
            $sut->remove($this->stagingDir);
        }, IOException::class, $message, $previous::class);
    }
}
