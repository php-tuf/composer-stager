<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Finder\Service;

use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface;
use PhpTuf\ComposerStager\Internal\Finder\Service\ExecutableFinder;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Process\ExecutableFinder as SymfonyExecutableFinder;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Finder\Service\ExecutableFinder
 *
 * @covers ::__construct
 * @covers ::find
 */
final class ExecutableFinderUnitTest extends TestCase
{
    private SymfonyExecutableFinder|ObjectProphecy $symfonyExecutableFinder;

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
        $translatorFactory = new TestTranslatableFactory();

        return new ExecutableFinder($executableFinder, $translatorFactory);
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
        $this->symfonyExecutableFinder
            ->addSuffix('.phar')
            ->shouldBeCalledOnce()
            ->willReturn(null);
        $this->symfonyExecutableFinder
            ->find($name)
            ->shouldBeCalledOnce()
            ->willReturn(null);
        $sut = $this->createSut();

        $message = sprintf('The %s executable cannot be found. Make sure it\'s installed and in the $PATH', $name);
        self::assertTranslatableException(static function () use ($sut, $name) {
            $sut->find($name);
        }, LogicException::class, $message);
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
        $this->symfonyExecutableFinder
            ->addSuffix('.phar')
            ->willReturn(null);
        $this->symfonyExecutableFinder
            ->find('composer')
            ->willReturn(null);
        $sut = $this->createSut();

        $message = 'The composer executable cannot be found. Make sure it\'s installed and in the $PATH';
        self::assertTranslatableException(static function () use ($sut) {
            $sut->find('composer');
        }, LogicException::class, $message);
    }
}
