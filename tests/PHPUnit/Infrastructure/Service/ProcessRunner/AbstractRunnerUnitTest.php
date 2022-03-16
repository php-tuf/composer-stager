<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\ProcessRunner;

use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Infrastructure\Factory\Process\ProcessFactoryInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinderInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\ProcessRunner\AbstractRunner;
use PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Service\ProcessOutputCallback\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Prophecy\Argument;
use Symfony\Component\Process\Exception\ProcessFailedException as SymfonyProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\ProcessRunner\AbstractRunner
 *
 * @covers ::__construct
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinderInterface|\Prophecy\Prophecy\ObjectProphecy $executableFinder
 * @property \PhpTuf\ComposerStager\Infrastructure\Factory\Process\ProcessFactoryInterface|\Prophecy\Prophecy\ObjectProphecy $processFactory
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Process\Process $process
 */
class AbstractRunnerUnitTest extends TestCase
{
    private const COMMAND_NAME = 'test';

    public function setUp(): void
    {
        $this->executableFinder = $this->prophesize(ExecutableFinderInterface::class);
        $this->executableFinder
            ->find(Argument::any())
            ->willReturnArgument();
        $this->processFactory = $this->prophesize(ProcessFactoryInterface::class);
        $this->process = $this->prophesize(Process::class);
        $this->process
            ->setTimeout(Argument::any())
            ->willReturn($this->process);
    }

    protected function createSut($executableName = null)
    {
        $executableName = $executableName ?? self::COMMAND_NAME;
        $executableFinder = $this->executableFinder->reveal();
        $process = $this->process->reveal();
        $this->processFactory
            ->create(Argument::cetera())
            ->willReturn($process);
        $processFactory = $this->processFactory->reveal();

        // Create a concrete implementation for testing since the SUT, being
        // abstract, can't be instantiated directly.
        return new class ($executableName, $executableFinder, $processFactory) extends AbstractRunner
        {
            private $executableName;

            public function __construct(
                string $executableName,
                ExecutableFinderInterface $executableFinder,
                ProcessFactoryInterface $processFactory
            ) {
                parent::__construct($executableFinder, $processFactory);

                $this->executableName = $executableName;
            }

            protected function executableName(): string
            {
                return $this->executableName;
            }
        };
    }

    /**
     * @covers ::executableName
     * @covers ::findExecutable
     * @covers ::run
     *
     * @dataProvider providerRun
     */
    public function testRun($executableName, $givenCommand, $expectedCommand, $callback, $timeout): void
    {
        $this->executableFinder
            ->find($executableName)
            ->willReturnArgument()
            ->shouldBeCalledOnce();
        $this->process
            ->setTimeout($timeout)
            ->shouldBeCalledOnce();
        $this->process
            ->mustRun($callback)
            ->shouldBeCalledOnce();
        $this->processFactory
            ->create($expectedCommand)
            ->shouldBeCalled()
            ->willReturn($this->process);

        $sut = $this->createSut($executableName);

        $sut->run($givenCommand, $callback, $timeout);
    }

    public function providerRun(): array
    {
        return [
            [
                'executableName' => 'one',
                'givenCommand' => [],
                'expectedCommand' => ['one'],
                'callback' => null,
                'timeout' => null,
            ],
            [
                'executableName' => 'two',
                'givenCommand' => ['three', 'four'],
                'expectedCommand' => ['two', 'three', 'four'],
                'callback' => null,
                'timeout' => 100,
            ],
            [
                'executableName' => 'five',
                'givenCommand' => [],
                'expectedCommand' => ['five'],
                'callback' => new TestProcessOutputCallback(),
                'timeout' => 200,
            ],
        ];
    }

    /**
     * @covers ::findExecutable
     * @covers ::run
     */
    public function testRunFailedException(): void
    {
        $this->expectException(ProcessFailedException::class);

        $exception = $this->prophesize(SymfonyProcessFailedException::class);
        $exception = $exception->reveal();
        $this->process
            ->mustRun(Argument::cetera())
            ->willThrow($exception);

        $sut = $this->createSut();

        $sut->run([self::COMMAND_NAME]);
    }

    /**
     * @covers ::findExecutable
     * @covers ::run
     */
    public function testRunFindExecutableException(): void
    {
        $this->expectException(IOException::class);

        $exception = $this->prophesize(IOException::class);
        $exception = $exception->reveal();
        $this->executableFinder
            ->find(Argument::any())
            ->willThrow($exception);

        $sut = $this->createSut();

        $sut->run([self::COMMAND_NAME]);
    }
}
