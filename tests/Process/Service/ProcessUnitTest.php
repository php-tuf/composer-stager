<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Process\Service;

use PhpTuf\ComposerStager\API\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Exception\RuntimeException;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\Internal\Process\Factory\SymfonyProcessFactoryInterface;
use PhpTuf\ComposerStager\Internal\Process\Service\Process;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\ProcessHelper;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Process\Process as SymfonyProcess;

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

    private function createSut(array $givenConstructorArguments, array $expectedCommand): Process
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
     * @covers ::setTimeout
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality(
        array $givenConstructorArguments,
        array $expectedCommand,
        array $givenMustRunArguments,
        ?OutputCallbackInterface $expectedMustRunArguments,
        array $givenSetTimeoutArguments,
        string $output,
    ): void {
        $this->symfonyProcess
            ->mustRun($expectedMustRunArguments)
            ->shouldBeCalledOnce()
            ->willReturn($this->symfonyProcess);
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

        $actualMustRunReturn = $sut->mustRun(...$givenMustRunArguments);
        $actualSetTimeoutReturn = $sut->setTimeout(...$givenSetTimeoutArguments);
        $actualOutput = $sut->getOutput();

        self::assertSame($sut, $actualMustRunReturn, 'Returned "self" from ::mustRun().');
        self::assertSame($sut, $actualSetTimeoutReturn, 'Returned "self" from ::timeout().');
        self::assertSame($output, $actualOutput, 'Returned correct output.');
    }

    public function providerBasicFunctionality(): array
    {
        return [
            'Minimum arguments' => [
                'givenConstructorArguments' => [],
                'expectedCommand' => [],
                'givenMustRunArguments' => [],
                'expectedMustRunArguments' => null,
                'givenSetTimeoutArguments' => [ProcessInterface::DEFAULT_TIMEOUT],
                'output' => 'Minimum arguments output',
            ],
            'Nullable arguments' => [
                'givenConstructorArguments' => [['nullable', 'arguments']],
                'expectedCommand' => ['nullable', 'arguments'],
                'givenMustRunArguments' => [null],
                'expectedMustRunArguments' => null,
                'givenSetTimeoutArguments' => [ProcessInterface::DEFAULT_TIMEOUT],
                'output' => 'Nullable arguments output',
            ],
            'Simple arguments' => [
                'givenConstructorArguments' => [['simple', 'arguments']],
                'expectedCommand' => ['simple', 'arguments'],
                'givenMustRunArguments' => [new TestOutputCallback()],
                'expectedMustRunArguments' => new TestOutputCallback(),
                'givenSetTimeoutArguments' => [42],
                'output' => 'Simple arguments output',
            ],
        ];
    }

    /** @covers ::getOutput */
    public function testGetOutputException(): void
    {
        $previous = ProcessHelper::createSymfonyProcessFailedException();
        $this->symfonyProcess
            ->getOutput()
            ->willThrow($previous);
        $sut = $this->createSut([], []);

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
        $sut = $this->createSut([], []);

        $expectedExceptionMessage = sprintf('Failed to run process: %s', $previous->getMessage());
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->mustRun();
        }, RuntimeException::class, $expectedExceptionMessage, $previous::class);
    }

    /** @covers ::setTimeout */
    public function testSetTimeoutException(): void
    {
        $previous = ProcessHelper::createSymfonyProcessFailedException();
        $this->symfonyProcess
            ->setTimeout(Argument::cetera())
            ->willThrow($previous);
        $sut = $this->createSut([], []);

        $expectedExceptionMessage = sprintf('Failed to set process timeout: %s', $previous->getMessage());
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->setTimeout();
        }, InvalidArgumentException::class, $expectedExceptionMessage, $previous::class);
    }
}
