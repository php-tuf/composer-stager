<?php

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Process;

use PhpTuf\ComposerStager\Exception\FileNotFoundException;
use PhpTuf\ComposerStager\Infrastructure\Process\ComposerFinder;
use PhpTuf\ComposerStager\Tests\TestCase;
use Symfony\Component\Process\ExecutableFinder;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Process\ComposerFinder
 * @covers \PhpTuf\ComposerStager\Infrastructure\Process\ComposerFinder::__construct
 * @covers \PhpTuf\ComposerStager\Infrastructure\Process\ComposerFinder::find
 * @uses \PhpTuf\ComposerStager\Exception\FileNotFoundException
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 *
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Process\ExecutableFinder $executableFinder
 */
class ComposerFinderTest extends TestCase
{
    protected function setUp(): void
    {
        $this->executableFinder = $this->prophesize(ExecutableFinder::class);
        $this->executableFinder
            ->find('composer')
            ->willReturnArgument();
    }

    private function createSut(): ComposerFinder
    {
        $executableFinder = $this->executableFinder->reveal();
        return new ComposerFinder($executableFinder);
    }

    /**
     * @dataProvider providerFind
     */
    public function testFind($path): void
    {
        $this->executableFinder
            ->addSuffix('.phar')
            ->shouldBeCalledOnce()
            ->willReturn(null);
        $this->executableFinder
            ->find('composer')
            ->shouldBeCalledOnce()
            ->willReturn($path);

        $sut = $this->createSut();

        $expected = $sut->find();
        // Call again to test result caching.
        $sut->find();

        self::assertSame($path, $expected, 'Returned correct path');
    }

    public function providerFind(): array
    {
        return [
            ['/lorem/ipsum'],
            ['/dolor/sit'],
        ];
    }

    public function testFindNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessageMatches('/found/');
        $this->executableFinder
            ->addSuffix('.phar')
            ->shouldBeCalledOnce()
            ->willReturn(null);
        $this->executableFinder
            ->find('composer')
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $sut = $this->createSut();

        $sut->find();
    }

    /**
     * Make sure ::find caches result when Composer is not found.
     */
    public function testFindNotFoundCaching(): void
    {
        $this->executableFinder
            ->addSuffix('.phar')
            ->shouldBeCalledOnce()
            ->willReturn(null);
        $this->executableFinder
            ->find('composer')
            ->shouldBeCalledOnce()
            ->willReturn(null);
        $sut = $this->createSut();

        try {
            $sut->find();
        } catch (FileNotFoundException $e) {
        }
        try {
            $sut->find();
        } catch (FileNotFoundException $e) {
        }
    }
}
