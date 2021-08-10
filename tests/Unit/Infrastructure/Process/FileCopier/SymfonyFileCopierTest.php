<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Infrastructure\Process\FileCopier;

use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\SymfonyFileCopier;
use PhpTuf\ComposerStager\Tests\Unit\Domain\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\Unit\TestCase;
use Prophecy\Argument;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\SymfonyFileCopier
 * @covers ::__construct
 * @covers ::copy
 * @covers ::createIterator
 *
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem filesystem
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Finder\Finder finder
 */
class SymfonyFileCopierTest extends TestCase
{
    public function setUp(): void
    {
        $this->filesystem = $this->prophesize(Filesystem::class);
        $this->finder = $this->prophesize(Finder::class);
        $this->finder
            ->in(Argument::any())
            ->willReturn($this->finder);
        $this->finder
            ->filter(Argument::any())
            ->willReturn($this->finder);
    }

    protected function createSut(): SymfonyFileCopier
    {
        $filesystem = $this->filesystem->reveal();
        $finder = $this->finder->reveal();
        return new SymfonyFileCopier($filesystem, $finder);
    }

    /**
     * @dataProvider providerCopy
     */
    public function testCopy($from, $to, $exclusions, $callback, $givenTimeout, $expectedTimeout): void
    {
        $this->filesystem
            ->mirror($from, $to, Argument::type(Finder::class))
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->copy($from, $to, $exclusions, $callback, $givenTimeout);

        self::assertSame((string) $expectedTimeout, ini_get('max_execution_time'), 'Correctly set process timeout.');
    }

    public function providerCopy(): array
    {
        return [
            [
                'from' => '.',
                'to' => 'ipsum/lorem',
                'exclusions' => [],
                'callback' => null,
                'givenTimeout' => null,
                'expectedTimeout' => 0,
            ],
            [
                'from' => '..',
                'to' => 'dolor/sit',
                'exclusions' => [
                    'amet',
                    'consectetur',
                ],
                'callback' => new TestProcessOutputCallback(),
                'givenTimeout' => 10,
                'expectedTimeout' => 10,
            ],
        ];
    }

    /**
     * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     * @uses \PhpTuf\ComposerStager\Exception\PathException
     */
    public function testCopyDirectoryNotFound(): void
    {
        $this->expectException(DirectoryNotFoundException::class);

        $this->finder
            ->in(Argument::any())
            ->willThrow(\Symfony\Component\Finder\Exception\DirectoryNotFoundException::class);
        $sut = $this->createSut();

        $sut->copy('.', 'lorem', []);
    }

    public function testCopyFailure(): void
    {
        $this->expectException(ProcessFailedException::class);

        $this->filesystem
            ->mirror(Argument::cetera())
            ->willThrow(IOException::class);
        $copier = $this->createSut();

        $copier->copy('.', 'lorem', []);
    }
}
