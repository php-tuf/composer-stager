<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\ProcessRunner\Service;

use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Exception\RuntimeException;
use PhpTuf\ComposerStager\Domain\ProcessOutputCallback\Service\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Infrastructure\Finder\Service\ExecutableFinderInterface;
use PhpTuf\ComposerStager\Infrastructure\Process\Factory\ProcessFactoryInterface;
use PhpTuf\ComposerStager\Infrastructure\ProcessRunner\Service\AbstractRunner;
use PhpTuf\ComposerStager\Tests\ProcessOutputCallback\Service\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableMessage;
use Prophecy\Argument;
use Symfony\Component\Process\Exception\ProcessFailedException as SymfonyProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\ProcessRunner\Service\AbstractRunner
 *
 * @covers ::__construct
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\TranslatableExceptionTrait
 * @uses \PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableAwareTrait
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\Finder\Service\ExecutableFinderInterface|\Prophecy\Prophecy\ObjectProphecy $executableFinder
 * @property \PhpTuf\ComposerStager\Infrastructure\Process\Factory\ProcessFactoryInterface|\Prophecy\Prophecy\ObjectProphecy $processFactory
 * @property \Symfony\Component\Process\Process|\Prophecy\Prophecy\ObjectProphecy $process
 */
final class AbstractRunnerUnitTest extends TestCase
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

    private function createSut($executableName = null): AbstractRunner
    {
        $executableName ??= self::COMMAND_NAME;
        $executableFinder = $this->executableFinder->reveal();
        $process = $this->process->reveal();
        $this->processFactory
            ->create(Argument::cetera())
            ->willReturn($process);
        $processFactory = $this->processFactory->reveal();
        $translatableFactory = new TestTranslatableFactory();

        // Create a concrete implementation for testing since the SUT, being
        // abstract, can't be instantiated directly.
        return new class ($executableFinder, $executableName, $processFactory, $translatableFactory) extends AbstractRunner
        {
            public function __construct(
                ExecutableFinderInterface $executableFinder,
                private readonly string $executableName,
                ProcessFactoryInterface $processFactory,
                TranslatableFactoryInterface $translatableFactory,
            ) {
                parent::__construct($executableFinder, $processFactory, $translatableFactory);
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
    public function testRun(
        string $executableName,
        array $givenCommand,
        array $expectedCommand,
        ?ProcessOutputCallbackInterface $callback,
        ?int $timeout,
    ): void {
        $this->executableFinder
            ->find($executableName)
            ->willReturnArgument()
            ->shouldBeCalledOnce();
        $this->process
            ->setTimeout($timeout)
            ->shouldBeCalledOnce()
            ->willReturn($this->process);
        $this->process
            ->mustRun($callback)
            ->shouldBeCalledOnce()
            ->willReturn($this->process);
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
        $previous = $this->prophesize(SymfonyProcessFailedException::class)->reveal();
        $this->process
            ->mustRun(Argument::cetera())
            ->willThrow($previous);

        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut) {
            $sut->run([self::COMMAND_NAME]);
        }, RuntimeException::class, null, $previous::class);
    }

    /**
     * @covers ::findExecutable
     * @covers ::run
     */
    public function testRunFindExecutableException(): void
    {
        $previous = new IOException(new TestTranslatableMessage());
        $this->executableFinder
            ->find(Argument::any())
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut) {
            $sut->run([self::COMMAND_NAME]);
        }, $previous::class);
    }
}
