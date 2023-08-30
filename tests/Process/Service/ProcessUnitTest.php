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
use PhpTuf\ComposerStager\Tests\TestUtils\TestStringable;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use stdClass;
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
     * @covers ::assertValidEnvName
     * @covers ::assertValidEnvValue
     * @covers ::getEnv
     * @covers ::getOutput
     * @covers ::mustRun
     * @covers ::run
     * @covers ::setEnv
     * @covers ::setTimeout
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality(
        array $givenConstructorArguments,
        array $expectedCommand,
        array $givenRunArguments,
        ?OutputCallbackAdapterInterface $expectedRunArgument,
        array $envVars,
        array $givenSetTimeoutArguments,
        string $output,
    ): void {
        $expectedRunReturn = 0;
        $this->symfonyProcess
            ->getEnv()
            ->shouldBeCalledOnce()
            ->willReturn($envVars);
        $this->symfonyProcess
            ->getOutput()
            ->shouldBeCalledOnce()
            ->willReturn($output);
        $this->symfonyProcess
            ->mustRun($expectedRunArgument)
            ->shouldBeCalledOnce()
            ->willReturn($this->symfonyProcess);
        $this->symfonyProcess
            ->run($expectedRunArgument)
            ->shouldBeCalledOnce()
            ->willReturn($expectedRunReturn);
        $this->symfonyProcess
            ->setEnv($envVars)
            ->shouldBeCalledOnce()
            ->willReturn($this->symfonyProcess);
        $this->symfonyProcess
            ->setTimeout(...$givenSetTimeoutArguments)
            ->shouldBeCalledOnce()
            ->willReturn($this->symfonyProcess);
        $this->symfonyProcessFactory
            ->create($expectedCommand)
            ->shouldBeCalledOnce();
        $sut = $this->createSut($givenConstructorArguments, $expectedCommand);

        $actualSetEnvReturn = $sut->setEnv($envVars);
        $actualEnv = $sut->getEnv();
        $actualOutput = $sut->getOutput();
        $actualMustRunReturn = $sut->mustRun(...$givenRunArguments);
        $actualRunReturn = $sut->run(...$givenRunArguments);
        $actualSetTimeoutReturn = $sut->setTimeout(...$givenSetTimeoutArguments);

        self::assertSame($actualEnv, $envVars, 'Returned correct output.');
        self::assertSame($output, $actualOutput, 'Returned correct output.');
        self::assertSame($sut, $actualMustRunReturn, 'Returned "self" from ::mustRun().');
        self::assertSame($expectedRunReturn, $actualRunReturn, 'Returned correct status code from ::run().');
        self::assertSame($sut, $actualSetEnvReturn, 'Returned "self" from ::setEnv().');
        self::assertSame($sut, $actualSetTimeoutReturn, 'Returned "self" from ::setTimeout().');
    }

    public function providerBasicFunctionality(): array
    {
        return [
            'Minimum arguments' => [
                'givenConstructorArguments' => [],
                'expectedCommand' => [],
                'givenRunArguments' => [],
                'expectedRunArgument' => new OutputCallbackAdapter(null),
                'envVars' => [],
                'givenSetTimeoutArguments' => [ProcessInterface::DEFAULT_TIMEOUT],
                'output' => 'Minimum arguments output',
            ],
            'Nullable arguments' => [
                'givenConstructorArguments' => [['nullable', 'arguments']],
                'expectedCommand' => ['nullable', 'arguments'],
                'givenRunArguments' => [null],
                'expectedRunArgument' => new OutputCallbackAdapter(null),
                'envVars' => [],
                'givenSetTimeoutArguments' => [ProcessInterface::DEFAULT_TIMEOUT],
                'output' => 'Nullable arguments output',
            ],
            'Simple arguments' => [
                'givenConstructorArguments' => [['simple', 'arguments']],
                'expectedCommand' => ['simple', 'arguments'],
                'givenRunArguments' => [new TestOutputCallback()],
                'expectedRunArgument' => new OutputCallbackAdapter(new TestOutputCallback()),
                'envVars' => [
                    'STRING' => 'example',
                    'STRINGABLE' => new TestStringable('example'),
                ],
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
            'success' => [0],
            'failure' => [1],
            'arbitrary' => [42],
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
            'SymfonyLogicException' => [new SymfonyLogicException('SymfonyLogicException')],
            'SymfonyRuntimeException' => [new SymfonyRuntimeException()],
            'Exception' => [new Exception('Exception')],
        ];
    }

    /**
     * @covers ::assertValidEnv
     * @covers ::assertValidEnvName
     *
     * @dataProvider providerSetEnvInvalidNames
     */
    public function testSetEnvInvalidNames(array $values, string $invalidName): void
    {
        $this->symfonyProcess
            ->setEnv(Argument::cetera())
            ->shouldNotBeCalled();
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut, $values): void {
            $sut->setEnv($values);
        }, InvalidArgumentException::class, sprintf(
            'Environment variable names must be non-zero-length strings. Got %s.',
            $invalidName,
        ));
    }

    public function providerSetEnvInvalidNames(): array
    {
        return [
            'Empty string' => [
                'values' => ['' => 'ONE'],
                'invalidName' => "''",
            ],
            'Integer' => [
                'values' => [42 => '42'],
                'invalidName' => '42',
            ],
            'Mixed with valid values' => [
                'values' => [
                    'ONE' => 'ONE',
                    42 => '42',
                    'THREE' => 'THREE',
                ],
                'invalidName' => '42',
            ],
        ];
    }

    /**
     * @covers ::assertValidEnv
     * @covers ::assertValidEnvValue
     *
     * @dataProvider providerSetEnvInvalidValues
     */
    public function testSetEnvInvalidValues(array $values, string $invalidValue): void
    {
        $this->symfonyProcess
            ->setEnv(Argument::cetera())
            ->shouldNotBeCalled();
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut, $values): void {
            $sut->setEnv($values);
        }, InvalidArgumentException::class, sprintf(
            'Environment variable values must be strings, stringable, or false to unset. Got %s.',
            $invalidValue,
        ));
    }

    public function providerSetEnvInvalidValues(): array
    {
        return [
            'Array' => [
                'values' => ['ARRAY' => []],
                'invalidValue' => 'array',
            ],
            'Integer' => [
                'values' => ['INTEGER' => 42],
                'invalidValue' => '42',
            ],
            'Object' => [
                'values' => ['OBJECT' => new stdClass()],
                'invalidValue' => 'stdClass',
            ],
            'Mixed with valid values' => [
                'values' => [
                    'ONE' => 'ONE',
                    'TWO' => 42,
                    'THREE' => 'THREE',
                ],
                'invalidValue' => '42',
            ],
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
