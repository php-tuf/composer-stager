<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Infrastructure\Filesystem;

use PhpTuf\ComposerStager\Exception\IOException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem;
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

    private function createSut(): Filesystem
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
     * @covers ::remove
     *
     * @dataProvider providerRemove
     */
    public function testRemove($path): void
    {
        $this->symfonyFilesystem
            ->remove($path)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->remove($path);
    }

    public function providerRemove(): array
    {
        return [
            ['path' => '/lorem/ipsum'],
            ['path' => '/dolor/sit'],
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
