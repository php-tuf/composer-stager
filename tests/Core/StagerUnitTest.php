<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Core;

use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Exception\RuntimeException;
use PhpTuf\ComposerStager\API\Precondition\Service\StagerPreconditionsInterface;
use PhpTuf\ComposerStager\API\Process\Service\ComposerProcessRunnerInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\Internal\Core\Stager;
use PhpTuf\ComposerStager\Tests\Process\Service\TestOutputCallback;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\Domain;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableExceptionMessage;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableMessage;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Core\Stager
 *
 * @covers \PhpTuf\ComposerStager\Internal\Core\Stager
 */
final class StagerUnitTest extends TestCase
{
    private const INERT_COMMAND = 'about';

    private ComposerProcessRunnerInterface|ObjectProphecy $composerRunner;
    private StagerPreconditionsInterface|ObjectProphecy $preconditions;

    protected function setUp(): void
    {
        $this->composerRunner = $this->prophesize(ComposerProcessRunnerInterface::class);
        $this->preconditions = $this->prophesize(StagerPreconditionsInterface::class);
    }

    private function createSut(): Stager
    {
        $composerRunner = $this->composerRunner->reveal();
        $preconditions = $this->preconditions->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new Stager($composerRunner, $preconditions, $translatableFactory);
    }

    /** @covers ::stage */
    public function testStageWithMinimumParams(): void
    {
        $activeDirPath = PathHelper::activeDirPath();
        $stagingDirPath = PathHelper::stagingDirPath();

        $this->preconditions
            ->assertIsFulfilled($activeDirPath, $stagingDirPath)
            ->shouldBeCalledOnce();
        $expectedCommand = [
            '--working-dir=' . PathHelper::stagingDirAbsolute(),
            self::INERT_COMMAND,
        ];
        $this->composerRunner
            ->run($expectedCommand, null, ProcessInterface::DEFAULT_TIMEOUT)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->stage([self::INERT_COMMAND], $activeDirPath, $stagingDirPath);
    }

    /** @dataProvider providerStageWithOptionalParams */
    public function testStageWithOptionalParams(
        array $givenCommand,
        array $expectedCommand,
        ?OutputCallbackInterface $callback,
        ?int $timeout,
    ): void {
        $activeDirPath = PathHelper::activeDirPath();
        $stagingDirPath = PathHelper::stagingDirPath();

        $this->preconditions
            ->assertIsFulfilled($activeDirPath, $stagingDirPath)
            ->shouldBeCalledOnce();
        $this->composerRunner
            ->run($expectedCommand, $callback, $timeout)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->stage($givenCommand, $activeDirPath, $stagingDirPath, $callback, $timeout);
    }

    public function providerStageWithOptionalParams(): array
    {
        return [
            [
                'givenCommand' => ['update'],
                'expectedCommand' => [
                    '--working-dir=' . PathHelper::stagingDirAbsolute(),
                    'update',
                ],
                'callback' => null,
                'timeout' => null,
            ],
            [
                'givenCommand' => [self::INERT_COMMAND],
                'expectedCommand' => [
                    '--working-dir=' . PathHelper::stagingDirAbsolute(),
                    self::INERT_COMMAND,
                ],
                'callback' => new TestOutputCallback(),
                'timeout' => 10,
            ],
        ];
    }

    /** @covers ::validateCommand */
    public function testCommandIsEmpty(): void
    {
        $message = 'The Composer command cannot be empty';
        $expectedExceptionMessage = new TestTranslatableExceptionMessage($message);
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut): void {
            $sut->stage([], PathHelper::activeDirPath(), PathHelper::stagingDirPath());
        }, InvalidArgumentException::class, $expectedExceptionMessage);
    }

    /** @covers ::validateCommand */
    public function testCommandContainsComposer(): void
    {
        $sut = $this->createSut();

        $expectedExceptionMessage = new TestTranslatableMessage(
            'The Composer command cannot begin with "composer"--it is implied',
            null,
            Domain::EXCEPTIONS,
        );
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->stage([
                'composer',
                self::INERT_COMMAND,
            ], PathHelper::activeDirPath(), PathHelper::stagingDirPath());
        }, InvalidArgumentException::class, $expectedExceptionMessage);
    }

    /**
     * @covers ::validateCommand
     *
     * @dataProvider providerCommandContainsWorkingDirOption
     */
    public function testCommandContainsWorkingDirOption(array $command): void
    {
        $sut = $this->createSut();

        $expectedExceptionMessage = new TestTranslatableMessage(
            'Cannot stage a Composer command containing the "--working-dir" (or "-d") option',
            null,
            Domain::EXCEPTIONS,
        );
        self::assertTranslatableException(static function () use ($sut, $command): void {
            $sut->stage($command, PathHelper::activeDirPath(), PathHelper::stagingDirPath());
        }, InvalidArgumentException::class, $expectedExceptionMessage);
    }

    public function providerCommandContainsWorkingDirOption(): array
    {
        return [
            [['--working-dir' => 'example/package']],
            [['-d' => 'example/package']],
        ];
    }

    /** @covers ::stage */
    public function testStagePreconditionsUnfulfilled(): void
    {
        $message = __METHOD__;
        $previous = self::createTestPreconditionException($message);
        $this->preconditions
            ->assertIsFulfilled(PathHelper::activeDirPath(), PathHelper::stagingDirPath(), Argument::cetera())
            ->shouldBeCalled()
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut): void {
            $sut->stage([self::INERT_COMMAND], PathHelper::activeDirPath(), PathHelper::stagingDirPath());
        }, PreconditionException::class, $previous->getTranslatableMessage());
    }

    /** @dataProvider providerExceptions */
    public function testExceptions(ExceptionInterface $exception, string $message): void
    {
        $this->composerRunner
            ->run(Argument::cetera())
            ->willThrow($exception);
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut): void {
            $sut->stage([self::INERT_COMMAND], PathHelper::activeDirPath(), PathHelper::stagingDirPath());
        }, RuntimeException::class, $message, $exception::class);
    }

    public function providerExceptions(): array
    {
        return [
            [
                'exception' => new IOException(new TestTranslatableExceptionMessage('one')),
                'message' => 'one',
            ],
            [
                'exception' => new LogicException(new TestTranslatableExceptionMessage('two')),
                'message' => 'two',
            ],
        ];
    }
}
