<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Filesystem;

use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Exception\LogicException;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem;
use PhpTuf\ComposerStager\Tests\Domain\Service\ProcessOutputCallback\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\TestCase;
use Prophecy\Argument;
use Symfony\Component\Filesystem\Exception\FileNotFoundException as SymfonyFileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException as SymfonyIOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem
 *
 * @covers \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem::__construct
 *
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 * @property \Symfony\Component\Filesystem\Filesystem|\Prophecy\Prophecy\ObjectProphecy $symfonyFilesystem
 */
final class FilesystemUnitTest extends TestCase
{
    protected function setUp(): void
    {
        $this->activeDir = $this->prophesize(PathInterface::class);
        $this->activeDir
            ->resolve()
            ->willReturn(self::ACTIVE_DIR);
        $this->stagingDir = $this->prophesize(PathInterface::class);
        $this->stagingDir
            ->resolve()
            ->willReturn(self::STAGING_DIR);
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
    public function testCopy($source, $destination): void
    {
        $this->activeDir
            ->resolve()
            ->willReturn($source);
        $this->stagingDir
            ->resolve()
            ->willReturn($destination);
        $this->symfonyFilesystem
            ->copy($source, $destination, true)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->copy(
            $this->activeDir->reveal(),
            $this->stagingDir->reveal(),
        );
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

        $this->symfonyFilesystem
            ->copy(Argument::cetera())
            ->willThrow(SymfonyIOException::class);
        $sut = $this->createSut();

        $sut->copy(
            $this->activeDir->reveal(),
            $this->stagingDir->reveal(),
        );
    }

    /** @covers ::copy */
    public function testCopySourceDirectoryNotFound(): void
    {
        $source = $this->activeDir->reveal();
        $destination = $this->stagingDir->reveal();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('The source file does not exist or is not a file at "%s"', $source->resolve()));

        $this->symfonyFilesystem
            ->copy(Argument::cetera())
            ->willThrow(SymfonyFileNotFoundException::class);
        $sut = $this->createSut();

        $sut->copy($source, $destination);
    }

    /** @covers ::copy */
    public function testCopyDirectoriesTheSame(): void
    {
        $this->activeDir
            ->resolve()
            ->willReturn('same');
        $source = $this->activeDir->reveal();
        $destination = $this->activeDir->reveal();

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
    public function testExists($path, $expected): void
    {
        $this->stagingDir
            ->resolve()
            ->willReturn($path);
        $this->symfonyFilesystem
            ->exists($path)
            ->shouldBeCalledOnce()
            ->willReturn($expected);
        $sut = $this->createSut();

        $actual = $sut->exists($this->stagingDir->reveal());

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
    public function testMkdir($dir): void
    {
        $this->stagingDir
            ->resolve()
            ->willReturn($dir);
        $this->symfonyFilesystem
            ->mkdir($dir)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->mkdir($this->stagingDir->reveal());
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

        $sut->mkdir($this->stagingDir->reveal());
    }

    /**
     * @covers ::remove
     *
     * @dataProvider providerRemove
     */
    public function testRemove($path, $callback, $givenTimeout, $expectedTimeout): void
    {
        $this->stagingDir
            ->resolve()
            ->willReturn($path);
        $this->symfonyFilesystem
            ->remove($path)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->remove($this->stagingDir->reveal(), $callback, $givenTimeout);

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

        $sut->remove($this->stagingDir->reveal());
    }
}
