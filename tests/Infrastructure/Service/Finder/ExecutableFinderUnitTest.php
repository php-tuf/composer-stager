<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Finder;

use PhpTuf\ComposerStager\Domain\Exception\LogicException;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinder;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinderInterface;
use PhpTuf\ComposerStager\Tests\TestCase;
use Prophecy\Argument;
use Symfony\Component\Process\ExecutableFinder as SymfonyExecutableFinder;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinder
 *
 * @covers ::__construct
 * @covers ::find
 *
 * @property \Symfony\Component\Process\ExecutableFinder|\Prophecy\Prophecy\ObjectProphecy $symfonyExecutableFinder
 */
final class ExecutableFinderUnitTest extends TestCase
{
    protected function setUp(): void
    {
        $this->symfonyExecutableFinder = $this->prophesize(SymfonyExecutableFinder::class);
        $this->symfonyExecutableFinder
            ->find(Argument::any())
            ->willReturnArgument();
    }

    private function createSut(): ExecutableFinderInterface
    {
        $executableFinder = $this->symfonyExecutableFinder->reveal();

        return new ExecutableFinder($executableFinder);
    }

    /** @dataProvider providerFind */
    public function testFind(string $firstCommandName, string $firstPath, string $secondCommandName): void
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
        // Find something else to test cache isolation.
        $secondPath = $sut->find($secondCommandName);

        self::assertSame($firstExpected, $firstPath, 'Returned correct first path');
        self::assertSame($secondCommandName, $secondPath, 'Returned correct second path (isolated path cache)');
    }

    public function providerFind(): array
    {
        return [
            [
                'firstCommandName' => 'two',
                'firstPath' => '/one/two/two',
                'secondCommandName' => 'three',
            ],
            [
                'firstCommandName' => 'seven',
                'firstPath' => '/four/five/seven',
                'secondCommandName' => 'eight',
            ],
        ];
    }

    /** @dataProvider providerFindNotFound */
    public function testFindNotFound(string $name): void
    {
        $this->expectException(LogicException::class);
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
            ['name' => 'one'],
            ['name' => 'two'],
        ];
    }

    /** Make sure ::find caches result when Composer is not found. */
    public function testFindNotFoundCaching(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The "composer" executable cannot be found. Make sure it\'s installed and in the $PATH');

        $this->symfonyExecutableFinder
            ->addSuffix('.phar')
            ->willReturn(null);
        $this->symfonyExecutableFinder
            ->find('composer')
            ->willReturn(null);
        $sut = $this->createSut();

        $sut->find('composer');
    }
}
