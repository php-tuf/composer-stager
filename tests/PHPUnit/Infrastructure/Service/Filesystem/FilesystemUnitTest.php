<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\Filesystem;

use PhpTuf\ComposerStager\Domain\Exception\IOException;
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
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $symfonyFilesystem
 */
class FilesystemUnitTest extends TestCase
{
    protected function setUp(): void
    {
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
        $this->symfonyFilesystem
            ->copy($source, $destination, true)
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

        $this->symfonyFilesystem
            ->copy(Argument::cetera())
            ->willThrow(SymfonyIOException::class);
        $sut = $this->createSut();

        $sut->copy('source/index.php', 'destination/index.php');
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
