<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Infrastructure\Process\FileCopier;

use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\SymfonyFileCopier;
use PhpTuf\ComposerStager\Tests\Unit\Domain\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\Unit\TestCase;
use Prophecy\Argument;
use RecursiveCallbackFilterIterator;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\SymfonyFileCopier
 * @covers ::__construct
 * @covers ::createIterator
 *
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem filesystem
 */
class SymfonyFileCopierTest extends TestCase
{
    public function setUp(): void
    {
        $this->filesystem = $this->prophesize(Filesystem::class);
    }

    protected function createSut(): SymfonyFileCopier
    {
        $filesystem = $this->filesystem->reveal();
        return new SymfonyFileCopier($filesystem);
    }

    /**
     * @covers ::copy
     *
     * @dataProvider providerCopy
     */
    public function testCopy($from, $to, $exclusions, $callback, $givenTimeout, $expectedTimeout): void
    {
        $this->filesystem
            ->mirror($from, $to, Argument::type(RecursiveCallbackFilterIterator::class))
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
                'expecteTimeout' => 10,
            ],
        ];
    }

    /**
     * @covers ::copy
     */
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
