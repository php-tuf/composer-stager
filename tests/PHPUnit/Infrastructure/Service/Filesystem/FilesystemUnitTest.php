<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\Filesystem;

use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem;
use PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Service\ProcessOutputCallback\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Prophecy\Argument;
use Symfony\Component\Filesystem\Exception\IOException as SymfonyIOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem
 *
 * @covers \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem::__construct
 *
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $destination
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $source
 * @property \Symfony\Component\Filesystem\Filesystem|\Prophecy\Prophecy\ObjectProphecy $symfonyFilesystem
 */
final class FilesystemUnitTest extends TestCase
{
    protected function setUp(): void
    {
        $this->destination = $this->prophesize(PathInterface::class);
        $this->destination
            ->resolve()
            ->willReturn(self::ACTIVE_DIR);
        $this->source = $this->prophesize(PathInterface::class);
        $this->source
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
        $this->source
            ->resolve()
            ->willReturn($source);
        $this->destination
            ->resolve()
            ->willReturn($destination);
        $this->symfonyFilesystem
            ->copy($source, $destination, true)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->copy(
            $this->source->reveal(),
            $this->destination->reveal()
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
            $this->source->reveal(),
            $this->destination->reveal()
        );
    }

    /**
     * @covers ::exists
     *
     * @dataProvider providerExists
     */
    public function testExists($path, $expected): void
    {
        $this->symfonyFilesystem
            ->exists($path)
            ->shouldBeCalledOnce()
            ->willReturn($expected);
        $sut = $this->createSut();

        self::assertEquals($expected, $sut->exists($path), 'Correctly detected existence of path.');
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
        $this->symfonyFilesystem
            ->mkdir($dir)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->mkdir($dir);
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

        $sut->mkdir('example');
    }

    /**
     * @covers ::remove
     *
     * @dataProvider providerRemove
     */
    public function testRemove($path, $callback, $givenTimeout, $expectedTimeout): void
    {
        $this->symfonyFilesystem
            ->remove($path)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->remove($path, $callback, $givenTimeout);

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

        $sut->remove('/example/path');
    }
}
