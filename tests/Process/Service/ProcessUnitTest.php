<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Process\Service;

use Exception;
use PhpTuf\ComposerStager\API\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Exception\RuntimeException;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\Internal\Process\Factory\SymfonyProcessFactoryInterface;
use PhpTuf\ComposerStager\Internal\Process\Service\OutputCallbackAdapter;
use PhpTuf\ComposerStager\Internal\Process\Service\OutputCallbackAdapterInterface;
use PhpTuf\ComposerStager\Internal\Process\Service\Process;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\ProcessHelper;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Process\Exception\LogicException as SymfonyLogicException;
use Symfony\Component\Process\Exception\RuntimeException as SymfonyRuntimeException;
use Symfony\Component\Process\Process as SymfonyProcess;
use Throwable;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Process\Service\Process
 *
 * @covers ::__construct
 */
final class ProcessUnitTest extends TestCase
{
    private SymfonyProcessFactoryInterface|ObjectProphecy $symfonyProcessFactory;
    private SymfonyProcess|ObjectProphecy $symfonyProcess;

    protected function setUp(): void
    {
        $this->symfonyProcessFactory = $this->prophesize(SymfonyProcessFactoryInterface::class);
        $this->symfonyProcess = $this->prophesize(SymfonyProcess::class);
        $this->symfonyProcess
            ->mustRun(Argument::cetera())
            ->willReturn($this->symfonyProcess);
        $this->symfonyProcess
            ->setTimeout(Argument::cetera())
            ->willReturn($this->symfonyProcess);
        $this->symfonyProcess
            ->getOutput()
            ->willReturn('');
        $this->symfonyProcessFactory
            ->create(Argument::cetera())
            ->willReturn($this->symfonyProcess);
    }

    private function createSut(array $givenConstructorArguments = [], array $expectedCommand = []): Process
    {
        $symfonyProcess = $this->symfonyProcess->reveal();
        $this->symfonyProcessFactory
            ->create($expectedCommand)
            ->willReturn($symfonyProcess);
        $symfonyProcessFactory = $this->symfonyProcessFactory->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new Process($symfonyProcessFactory, $translatableFactory, ...$givenConstructorArguments);
    }

    /**
     * @covers ::__construct
     * @covers ::getOutput
     * @covers ::mustRun
     * @covers ::run
     * @covers ::setTimeout
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality(
        array $givenConstructorArguments,
        array $expectedCommand,
        array $givenRunArguments,
        ?OutputCallbackAdapterInterface $expectedRunArgument,
        array $givenSetTimeoutArguments,
        string $output,
    ): void {
        $expectedRunReturn = 0;
        $this->symfonyProcess
            ->mustRun($expectedRunArgument)
            ->shouldBeCalledOnce()
            ->willReturn($this->symfonyProcess);
        $this->symfonyProcess
            ->run($expectedRunArgument)
            ->shouldBeCalledOnce()
            ->willReturn($expectedRunReturn);
        $this->symfonyProcess
            ->setTimeout(...$givenSetTimeoutArguments)
            ->shouldBeCalledOnce()
            ->willReturn($this->symfonyProcess);
        $this->symfonyProcess
            ->getOutput()
            ->shouldBeCalledOnce()
            ->willReturn($output);
        $this->symfonyProcessFactory
            ->create($expectedCommand)
            ->shouldBeCalledOnce();
        $sut = $this->createSut($givenConstructorArguments, $expectedCommand);

        $actualMustRunReturn = $sut->mustRun(...$givenRunArguments);
        $actualRunReturn = $sut->run(...$givenRunArguments);
        $actualSetTimeoutReturn = $sut->setTimeout(...$givenSetTimeoutArguments);
        $actualOutput = $sut->getOutput();

        self::assertSame($sut, $actualMustRunReturn, 'Returned "self" from ::mustRun().');
        self::assertSame($expectedRunReturn, $actualRunReturn, 'Returned correct status code from ::run().');
        self::assertSame($sut, $actualSetTimeoutReturn, 'Returned "self" from ::timeout().');
        self::assertSame($output, $actualOutput, 'Returned correct output.');
    }

    public function providerBasicFunctionality(): array
    {
        return [
            'Minimum arguments' => [
                'givenConstructorArguments' => [],
                'expectedCommand' => [],
                'givenRunArguments' => [],
                'expectedRunArgument' => new OutputCallbackAdapter(null),
                'givenSetTimeoutArguments' => [ProcessInterface::DEFAULT_TIMEOUT],
                'output' => 'Minimum arguments output',
            ],
            'Nullable arguments' => [
                'givenConstructorArguments' => [['nullable', 'arguments']],
                'expectedCommand' => ['nullable', 'arguments'],
                'givenRunArguments' => [null],
                'expectedRunArgument' => new OutputCallbackAdapter(null),
                'givenSetTimeoutArguments' => [ProcessInterface::DEFAULT_TIMEOUT],
                'output' => 'Nullable arguments output',
            ],
            'Simple arguments' => [
                'givenConstructorArguments' => [['simple', 'arguments']],
                'expectedCommand' => ['simple', 'arguments'],
                'givenRunArguments' => [new TestOutputCallback()],
                'expectedRunArgument' => new OutputCallbackAdapter(new TestOutputCallback()),
                'givenSetTimeoutArguments' => [42],
                'output' => 'Simple arguments output',
            ],
        ];
    }

    /**
     * @covers ::run
     *
     * @dataProvider providerRunStatusCode
     */
    public function testRunStatusCode(int $expected): void
    {
        $this->symfonyProcess
            ->run(Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($expected);
        $sut = $this->createSut();

        $actual = $sut->run();

        self::assertSame($expected, $actual, 'Returned correct status code.');
    }

    public function providerRunStatusCode(): array
    {
        return [
            ['success' => 0],
            ['failure' => 1],
            ['arbitrary' => 42],
        ];
    }

    /** @covers ::getOutput */
    public function testGetOutputException(): void
    {
        $previous = ProcessHelper::createSymfonyProcessFailedException();
        $this->symfonyProcess
            ->getOutput()
            ->willThrow($previous);
        $sut = $this->createSut();

        $expectedExceptionMessage = sprintf('Failed to get process output: %s', $previous->getMessage());
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->getOutput();
        }, LogicException::class, $expectedExceptionMessage, $previous::class);
    }

    /** @covers ::mustRun */
    public function testMustRunException(): void
    {
        $previous = ProcessHelper::createSymfonyProcessFailedException();
        $this->symfonyProcess
            ->mustRun(Argument::cetera())
            ->willThrow($previous);
        $sut = $this->createSut();

        $expectedExceptionMessage = sprintf('Failed to run process: %s', $previous->getMessage());
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->mustRun();
        }, RuntimeException::class, $expectedExceptionMessage, $previous::class);
    }

    /**
     * @covers ::run
     *
     * @dataProvider providerRunException
     */
    public function testRunException(Throwable $previous): void
    {
        $this->symfonyProcess
            ->run(Argument::cetera())
            ->shouldBeCalled()
            ->willThrow($previous);
        $sut = $this->createSut();

        $expectedExceptionMessage = sprintf('Failed to run process: %s', $previous->getMessage());
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->run();
        }, RuntimeException::class, $expectedExceptionMessage, $previous::class);
    }

    public function providerRunException(): array
    {
        return [
            [new SymfonyLogicException('SymfonyLogicException')],
            [new SymfonyRuntimeException()],
            [new Exception('Exception')],
        ];
    }

    /** @covers ::setTimeout */
    public function testSetTimeoutException(): void
    {
        $previous = ProcessHelper::createSymfonyProcessFailedException();
        $this->symfonyProcess
            ->setTimeout(Argument::cetera())
            ->willThrow($previous);
        $sut = $this->createSut();

        $expectedExceptionMessage = sprintf('Failed to set process timeout: %s', $previous->getMessage());
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->setTimeout();
        }, InvalidArgumentException::class, $expectedExceptionMessage, $previous::class);
    }
}
