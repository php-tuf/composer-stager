<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Infrastructure\Filesystem;

use PhpTuf\ComposerStager\Exception\IOException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem;
use PhpTuf\ComposerStager\Tests\Unit\Domain\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\Unit\TestCase;
use Prophecy\Argument;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem
 * @covers \PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem::__construct
 *
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $symfonyFilesystem
 */
class FilesystemTest extends TestCase
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
     * @covers ::getcwd
     */
    public function testGetcwd(): void
    {
        $sut = $this->createSut();

        self::assertSame(getcwd(), $sut->getcwd());
    }

    /**
     * @covers ::copy
     *
     * @dataProvider providerCopy
     */
    public function testCopy($fromFile, $toFile): void
    {
        $this->symfonyFilesystem
            ->copy($fromFile, $toFile, true)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->copy($fromFile, $toFile);
    }

    public function providerCopy(): array
    {
        return [
            [
                'fromFile' => 'lorem',
                'toFile' => 'ipsum',
            ],
            [
                'fromFile' => 'dolor',
                'toFile' => 'sit',
            ],
        ];
    }

    /**
     * @covers ::copy
     */
    public function testCopyFailure(): void
    {
        $this->expectException(IOException::class);

        $this->symfonyFilesystem
            ->copy(Argument::cetera())
            ->willThrow(\Symfony\Component\Filesystem\Exception\IOException::class);
        $sut = $this->createSut();

        $sut->copy('lorem/index.php', 'ipsum/index.php');
    }

    /**
     * @dataProvider providerExists
     *
     * @covers ::exists
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
                'path' => '/lorem/ipsum',
                'expected' => true,
            ],
            [
                'path' => '/dolor/sit',
                'expected' => false,
            ],
        ];
    }

    /**
     * @covers ::isDir
     * @covers ::isFile
     *
     * @dataProvider providerIsDirIsFile
     */
    public function testIsDirIsFile($path, $isDir, $isFile): void
    {
        $sut = $this->createSut();

        self::assertEquals($isDir, $sut->isDir($path));
        self::assertEquals($isFile, $sut->isFile($path));
    }

    public function providerIsDirIsFile(): array
    {
        return [
            [
                'path' => __DIR__,
                'isDir' => true,
                'isFile' => false,
            ],
            [
                'path' => __FILE__,
                'isDir' => false,
                'isFile' => true,
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
            ['dir' => 'lorem'],
            ['dir' => 'ipsum'],
        ];
    }

    /**
     * @covers ::mkdir
     */
    public function testMkdirFailure(): void
    {
        $this->expectException(IOException::class);

        $this->symfonyFilesystem
            ->mkdir(Argument::any())
            ->willThrow(\Symfony\Component\Filesystem\Exception\IOException::class);
        $sut = $this->createSut();

        $sut->mkdir('lorem');
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
                'path' => '/lorem/ipsum',
                'callback' => null,
                'givenTimeout' => null,
                'expectedTimeout' => 0,
            ],
            [
                'path' => '/dolor/sit',
                'callback' => new TestProcessOutputCallback(),
                'givenTimeout' => 10,
                'expectedTimeout' => 10,
            ],
        ];
    }

    /**
     * @covers ::remove
     */
    public function testRemoveException(): void
    {
        $this->expectException(IOException::class);

        $this->symfonyFilesystem
            ->remove(Argument::any())
            ->willThrow(\Symfony\Component\Filesystem\Exception\IOException::class);
        $sut = $this->createSut();

        $sut->remove('/lorem/ipsum');
    }
}
