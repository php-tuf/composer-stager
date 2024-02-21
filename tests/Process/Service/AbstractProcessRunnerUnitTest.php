<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Process\Service;

use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface;
use PhpTuf\ComposerStager\API\Process\Factory\ProcessFactoryInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Internal\Process\Service\AbstractProcessRunner;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestDoubles\Process\Service\TestOutputCallback;
use PhpTuf\ComposerStager\Tests\TestDoubles\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\TestDoubles\Translation\Value\TestTranslatableExceptionMessage;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Process\Service\AbstractProcessRunner
 *
 * @covers ::__construct
 */
final class AbstractProcessRunnerUnitTest extends TestCase
{
    private const COMMAND_NAME = 'test';

    private ExecutableFinderInterface|ObjectProphecy $executableFinder;
    private ProcessFactoryInterface|ObjectProphecy $processFactory;
    private ProcessInterface|ObjectProphecy $process;

    protected function setUp(): void
    {
        $this->executableFinder = $this->prophesize(ExecutableFinderInterface::class);
        $this->executableFinder
            ->find(Argument::any())
            ->willReturnArgument();
        $this->processFactory = $this->prophesize(ProcessFactoryInterface::class);
        $this->process = $this->prophesize(ProcessInterface::class);
        $this->process
            ->mustRun(Argument::any())
            ->willReturn($this->process);
        $this->process
            ->setTimeout(Argument::any())
            ->willReturn($this->process);
    }

    private function createSut($executableName = null): AbstractProcessRunner
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
        return new class ($executableFinder, $executableName, $processFactory, $translatableFactory) extends AbstractProcessRunner
        {
            public function __construct(
                ExecutableFinderInterface $executableFinder,
                private readonly string $executableName,
                ProcessFactoryInterface $processFactory,
                TranslatableFactoryInterface $translatableFactory,
            ) {
                parent::__construct($executableFinder, $processFactory, $translatableFactory);
            }

            public function getTranslatableMessage(string $message): TranslatableInterface
            {
                return $this->t($message);
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
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality(
        string $executableName,
        array $givenRunArguments,
        array $expectedFactoryArguments,
        ?OutputCallbackInterface $callback,
        int $timeout,
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
            ->create(...$expectedFactoryArguments)
            ->shouldBeCalledOnce()
            ->willReturn($this->process);
        $sut = $this->createSut($executableName);

        $sut->run(...$givenRunArguments);
    }

    public function providerBasicFunctionality(): array
    {
        return [
            'Minimum values' => [
                'executableName' => 'one',
                'givenRunArguments' => [[]],
                'expectedFactoryArguments' => [['one'], null, []],
                'callback' => null,
                'timeout' => ProcessInterface::DEFAULT_TIMEOUT,
            ],
            'Default values' => [
                'executableName' => 'one',
                'givenRunArguments' => [['two'], null, []],
                'expectedFactoryArguments' => [['one', 'two'], null, []],
                'callback' => null,
                'timeout' => ProcessInterface::DEFAULT_TIMEOUT,
            ],
            'Simple values' => [
                'executableName' => 'one',
                'givenRunArguments' => [
                    ['two', 'three', 'four'],
                    PathTestHelper::arbitraryDirPath(),
                    ['ONE' => 'one', 'TWO' => 'two'],
                    new TestOutputCallback(),
                    100,
                ],
                'expectedFactoryArguments' => [
                    ['one', 'two', 'three', 'four'],
                    PathTestHelper::arbitraryDirPath(),
                    ['ONE' => 'one', 'TWO' => 'two'],
                ],
                'callback' => new TestOutputCallback(),
                'timeout' => 100,
            ],
        ];
    }

    /**
     * @covers ::findExecutable
     * @covers ::run
     */
    public function testRunCannotFindGivenExecutable(): void
    {
        $previous = new IOException(new TestTranslatableExceptionMessage());
        $this->executableFinder
            ->find(Argument::any())
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut): void {
            $sut->run([self::COMMAND_NAME]);
        }, $previous::class);
    }

    public function testIsTranslatable(): void
    {
        $message = 'test';
        $sut = $this->createSut();

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $translatable = $sut->getTranslatableMessage($message);

        self::assertSame($message, $translatable->trans());
    }
}
