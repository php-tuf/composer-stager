<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Infrastructure\Process;

use PhpTuf\ComposerStager\Exception\IOException;
use PhpTuf\ComposerStager\Infrastructure\Process\ExecutableFinder;
use PhpTuf\ComposerStager\Infrastructure\Process\ExecutableFinderInterface;
use PhpTuf\ComposerStager\Tests\Unit\TestCase;
use Prophecy\Argument;
use Symfony\Component\Process\ExecutableFinder as SymfonyExecutableFinder;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Process\ExecutableFinder
 * @covers ::__construct
 * @covers ::find
 * @covers ::getCache
 * @covers ::setCache
 * @uses \PhpTuf\ComposerStager\Exception\FileNotFoundException
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 *
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Process\ExecutableFinder $symfonyExecutableFinder
 */
class ExecutableFinderTest extends TestCase
{
    protected function setUp(): void
    {
        $this->symfonyExecutableFinder = $this->prophesize(SymfonyExecutableFinder::class);
        $this->symfonyExecutableFinder
            ->find(Argument::any())
            ->willReturnArgument();
    }

    protected function createSut(): ExecutableFinderInterface
    {
        $executableFinder = $this->symfonyExecutableFinder->reveal();
        return new ExecutableFinder($executableFinder);
    }

    /**
     * @dataProvider providerFind
     */
    public function testFind($firstCommandName, $firstPath, $secondCommandName): void
    {
        $this->symfonyExecutableFinder
            ->addSuffix('.phar')
            ->shouldBeCalled()
            ->willReturn(null);
        $this->symfonyExecutableFinder
            ->find($firstCommandName)
            ->shouldBeCalledOnce()
            ->willReturn($firstPath);

        $sut = $this->createSut();

        $firstExpected = $sut->find($firstCommandName);
        // Call again to test result caching.
        $sut->find($firstCommandName);
        // Find something else to test cache isolation.
        $secondPath = $sut->find($secondCommandName);

        self::assertSame($firstExpected, $firstPath, 'Returned correct first path');
        self::assertSame($secondCommandName, $secondPath, 'Returned correct second path (isolated path cache)');
    }

    public function providerFind(): array
    {
        return [
            [
                'firstCommandName' => 'dolor',
                'firstPath' => '/lorem/ipsum/dolor',
                'secondCommandName' => 'sit',
            ],
            [
                'firstCommandName' => 'adipiscing',
                'firstPath' => '/amet/consectetur/adipiscing',
                'secondCommandName' => 'elit',
            ],
        ];
    }

    /**
     * @dataProvider providerFindNotFound
     */
    public function testFindNotFound($name): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessageMatches("/{$name}.*found/");
        $this->symfonyExecutableFinder
            ->addSuffix('.phar')
            ->shouldBeCalledOnce()
            ->willReturn(null);
        $this->symfonyExecutableFinder
            ->find($name)
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $sut = $this->createSut();

        $sut->find($name);
    }

    public function providerFindNotFound(): array
    {
        return [
            ['name' => 'lorem'],
            ['name' => 'ipsum'],
        ];
    }

    /**
     * Make sure ::find caches result when Composer is not found.
     */
    public function testFindNotFoundCaching(): void
    {
        $this->symfonyExecutableFinder
            ->addSuffix('.phar')
            ->shouldBeCalledOnce()
            ->willReturn(null);
        $this->symfonyExecutableFinder
            ->find('composer')
            ->shouldBeCalledOnce()
            ->willReturn(null);
        $sut = $this->createSut();

        try {
            $sut->find('composer');
        } catch (IOException $e) {
        }
        try {
            $sut->find('composer');
        } catch (IOException $e) {
        }
    }
}
