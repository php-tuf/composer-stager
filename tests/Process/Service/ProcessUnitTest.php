<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Process\Service;

use Exception;
use PhpTuf\ComposerStager\API\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Exception\RuntimeException;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\Internal\Process\Factory\SymfonyProcessFactory;
use PhpTuf\ComposerStager\Internal\Process\Factory\SymfonyProcessFactoryInterface;
use PhpTuf\ComposerStager\Internal\Process\Service\OutputCallback;
use PhpTuf\ComposerStager\Internal\Process\Service\OutputCallbackAdapter;
use PhpTuf\ComposerStager\Internal\Process\Service\OutputCallbackAdapterInterface;
use PhpTuf\ComposerStager\Internal\Process\Service\Process;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestDoubles\TestStringable;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\ProcessTestHelper;
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

    private function createSut(array $givenConstructorArguments = [['executable']]): Process
    {
        $expectedCommand = reset($givenConstructorArguments);
        $symfonyProcess = $this->symfonyProcess->reveal();
        $this->symfonyProcessFactory
            ->create($expectedCommand, Argument::cetera())
            ->willReturn($symfonyProcess);
        $symfonyProcessFactory = $this->symfonyProcessFactory->reveal();
        $translatableFactory = self::createTranslatableFactory();

        return new Process($symfonyProcessFactory, $translatableFactory, ...$givenConstructorArguments);
    }

    /**
     * @covers ::__construct
     * @covers ::assertValidEnvName
     * @covers ::assertValidEnvValue
     * @covers ::getEnv
     * @covers ::getErrorOutput
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
        array $givenRunArguments,
        ?OutputCallbackAdapterInterface $expectedRunArgument,
        array $envVars,
        array $givenSetTimeoutArguments,
        string $output,
        string $errorOutput,
    ): void {
        $expectedCommand = reset($givenConstructorArguments);
        $expectedRunReturn = 0;
        $this->symfonyProcess
            ->getEnv()
            ->shouldBeCalledOnce()
            ->willReturn($envVars);
        $this->symfonyProcess
            ->getErrorOutput()
            ->shouldBeCalledOnce()
            ->willReturn($errorOutput);
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
            ->create($expectedCommand, Argument::cetera())
            ->shouldBeCalledOnce();
        $sut = $this->createSut($givenConstructorArguments);

        $sut->setEnv($envVars);
        $actualEnv = $sut->getEnv();
        $actualOutput = $sut->getOutput();
        $actualErrorOutput = $sut->getErrorOutput();
        $sut->mustRun(...$givenRunArguments);
        $actualRunReturn = $sut->run(...$givenRunArguments);
        $sut->setTimeout(...$givenSetTimeoutArguments);

        self::assertSame($actualEnv, $envVars, 'Returned correct output.');
        self::assertSame($output, $actualOutput, 'Returned correct output.');
        self::assertSame($errorOutput, $actualErrorOutput, 'Returned correct error output.');
        self::assertSame($expectedRunReturn, $actualRunReturn, 'Returned correct status code from ::run().');
    }

    public function providerBasicFunctionality(): array
    {
        return [
            'Minimum arguments' => [
                'givenConstructorArguments' => [[]],
                'givenRunArguments' => [],
                'expectedRunArgument' => new OutputCallbackAdapter(null),
                'envVars' => [],
                'givenSetTimeoutArguments' => [ProcessInterface::DEFAULT_TIMEOUT],
                'output' => 'Minimum arguments output',
                'errorOutput' => 'Minimum arguments error output',
            ],
            'Nullable arguments' => [
                'givenConstructorArguments' => [['nullable', 'arguments']],
                'givenRunArguments' => [null],
                'expectedRunArgument' => new OutputCallbackAdapter(null),
                'envVars' => [],
                'givenSetTimeoutArguments' => [ProcessInterface::DEFAULT_TIMEOUT],
                'output' => 'Nullable arguments output',
                'errorOutput' => 'Nullable arguments error output',
            ],
            'Simple arguments' => [
                'givenConstructorArguments' => [['simple', 'arguments']],
                'givenRunArguments' => [new OutputCallback()],
                'expectedRunArgument' => new OutputCallbackAdapter(new OutputCallback()),
                'envVars' => [
                    'STRING' => 'example',
                    'STRINGABLE' => new TestStringable('example'),
                ],
                'givenSetTimeoutArguments' => [42],
                'output' => 'Simple arguments output',
                'errorOutput' => 'Simple arguments error output',
            ],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::getEnv
     * @covers ::setEnv
     *
     * @dataProvider providerEnv
     */
    public function testEnv(array $optionalArguments, array $expectedInitialEnv, array $givenNewEnv): void
    {
        $symfonyProcessFactory = ContainerTestHelper::get(SymfonyProcessFactory::class);
        $translatableFactory = self::createTranslatableFactory();
        $sut = new Process($symfonyProcessFactory, $translatableFactory, ['arbitrary_command'], ...$optionalArguments);

        $actualInitialEnv = $sut->getEnv();
        $sut->setEnv($givenNewEnv);
        $actualUpdatedEnv = $sut->getEnv();

        self::assertSame($expectedInitialEnv, $actualInitialEnv);
        self::assertSame($givenNewEnv, $actualUpdatedEnv);
    }

    public function providerEnv(): array
    {
        return [
            'No env argument' => [
                'optionalArguments' => [],
                'expectedInitialEnv' => [],
                'givenNewEnv' => [],
            ],
            'Initial env, no change' => [
                'optionalArguments' => [
                    null,
                    [
                        'ONE' => 'one',
                        'TWO' => 'two',
                    ],
                ],
                'expectedInitialEnv' => [
                    'ONE' => 'one',
                    'TWO' => 'two',
                ],
                'givenNewEnv' => [],
            ],
            'No env argument, changed' => [
                'optionalArguments' => [null, ['one' => 'two']],
                'expectedInitialEnv' => ['one' => 'two'],
                'givenNewEnv' => [
                    'ONE' => 'one',
                    'TWO' => 'two',
                ],
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
        $previous = ProcessTestHelper::createSymfonyProcessFailedException();
        $this->symfonyProcess
            ->getOutput()
            ->willThrow($previous);
        $sut = $this->createSut();

        $expectedExceptionMessage = sprintf('Failed to get process output: %s', $previous->getMessage());
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->getOutput();
        }, LogicException::class, $expectedExceptionMessage, null, $previous::class);
    }

    /** @covers ::getErrorOutput */
    public function testGetErrorOutputException(): void
    {
        $previous = ProcessTestHelper::createSymfonyProcessFailedException();
        $this->symfonyProcess
            ->getErrorOutput()
            ->willThrow($previous);
        $sut = $this->createSut();

        $expectedExceptionMessage = sprintf('Failed to get process error output: %s', $previous->getMessage());
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->getErrorOutput();
        }, LogicException::class, $expectedExceptionMessage, null, $previous::class);
    }

    /** @covers ::mustRun */
    public function testMustRunException(): void
    {
        $previous = ProcessTestHelper::createSymfonyProcessFailedException();
        $this->symfonyProcess
            ->mustRun(Argument::cetera())
            ->willThrow($previous);
        $sut = $this->createSut();

        $expectedExceptionMessage = sprintf('Failed to run process: %s', $previous->getMessage());
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->mustRun();
        }, RuntimeException::class, $expectedExceptionMessage, null, $previous::class);
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
        }, RuntimeException::class, $expectedExceptionMessage, null, $previous::class);
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
        $previous = ProcessTestHelper::createSymfonyProcessFailedException();
        $this->symfonyProcess
            ->setTimeout(Argument::cetera())
            ->willThrow($previous);
        $sut = $this->createSut();

        $expectedExceptionMessage = sprintf('Failed to set process timeout: %s', $previous->getMessage());
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->setTimeout();
        }, InvalidArgumentException::class, $expectedExceptionMessage, null, $previous::class);
    }
}
