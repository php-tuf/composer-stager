<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Finder\Service;

use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface;
use PhpTuf\ComposerStager\Internal\Finder\Service\ExecutableFinder;
use PhpTuf\ComposerStager\Tests\Doubles\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\TestCase;
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

    public function testFind(): void
    {
        $command = 'command_name';
        $this->symfonyExecutableFinder
            ->addSuffix('.phar')
            ->shouldBeCalled()
            ->willReturn(null);
        $this->symfonyExecutableFinder
            ->find($command)
            ->shouldBeCalledOnce()
            ->willReturn($command);
        $sut = $this->createSut();

        $actual = $sut->find($command);

        self::assertSame($command, $actual, 'Returned correct path');
    }

    public function testFindNotFound(): void
    {
        $command = 'command_name';
        $this->symfonyExecutableFinder
            ->addSuffix('.phar')
            ->shouldBeCalledOnce()
            ->willReturn(null);
        $this->symfonyExecutableFinder
            ->find($command)
            ->shouldBeCalledOnce()
            ->willReturn(null);
        $sut = $this->createSut();

        $message = sprintf('The %s executable cannot be found. Make sure it\'s installed and in the $PATH', $command);
        self::assertTranslatableException(static function () use ($sut, $command): void {
            $sut->find($command);
        }, LogicException::class, $message);
    }
}
