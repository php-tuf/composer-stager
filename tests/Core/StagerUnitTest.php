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
use PhpTuf\ComposerStager\Internal\Process\Service\OutputCallback;
use PhpTuf\ComposerStager\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

#[CoversClass(Stager::class)]
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
        $translatableFactory = self::createTranslatableFactory();

        return new Stager($composerRunner, $preconditions, $translatableFactory);
    }

    public function testStageWithMinimumParams(): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();
        $timeout = ProcessInterface::DEFAULT_TIMEOUT;

        $this->preconditions
            ->assertIsFulfilled($activeDirPath, $stagingDirPath)
            ->shouldBeCalledOnce();
        $expectedCommand = [
            '--working-dir=' . self::stagingDirAbsolute(),
            self::INERT_COMMAND,
        ];
        $this->composerRunner
            ->run($expectedCommand, null, [], null, $timeout)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->stage([self::INERT_COMMAND], $activeDirPath, $stagingDirPath);
    }

    #[DataProvider('providerStageWithOptionalParams')]
    public function testStageWithOptionalParams(
        array $givenCommand,
        array $expectedCommand,
        ?OutputCallbackInterface $callback,
        int $timeout,
    ): void {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();

        $this->preconditions
            ->assertIsFulfilled($activeDirPath, $stagingDirPath)
            ->shouldBeCalledOnce();
        $this->composerRunner
            ->run($expectedCommand, null, [], $callback, $timeout)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->stage($givenCommand, $activeDirPath, $stagingDirPath, $callback, $timeout);
    }

    public static function providerStageWithOptionalParams(): array
    {
        return [
            'Minimum values ' => [
                'givenCommand' => ['update'],
                'expectedCommand' => [
                    '--working-dir=' . self::stagingDirAbsolute(),
                    'update',
                ],
                'callback' => null,
                'timeout' => 0,
            ],
            'Simple values ' => [
                'givenCommand' => [self::INERT_COMMAND, '--quiet'],
                'expectedCommand' => [
                    '--working-dir=' . self::stagingDirAbsolute(),
                    self::INERT_COMMAND,
                    '--quiet',
                ],
                'callback' => new OutputCallback(),
                'timeout' => 10,
            ],
        ];
    }

    public function testCommandIsEmpty(): void
    {
        $message = 'The Composer command cannot be empty';
        $expectedExceptionMessage = self::createTranslatableExceptionMessage($message);
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut): void {
            $sut->stage([], self::activeDirPath(), self::stagingDirPath());
        }, InvalidArgumentException::class, $expectedExceptionMessage);
    }

    public function testCommandContainsComposer(): void
    {
        $sut = $this->createSut();

        $expectedExceptionMessage = self::createTranslatableExceptionMessage(
            'The Composer command cannot begin with "composer"--it is implied',
        );
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->stage([
                'composer',
                self::INERT_COMMAND,
            ], self::activeDirPath(), self::stagingDirPath());
        }, InvalidArgumentException::class, $expectedExceptionMessage);
    }

    #[DataProvider('providerCommandContainsWorkingDirOption')]
    public function testCommandContainsWorkingDirOption(array $command): void
    {
        $sut = $this->createSut();

        $expectedExceptionMessage = self::createTranslatableExceptionMessage(
            'Cannot stage a Composer command containing the "--working-dir" (or "-d") option',
        );
        self::assertTranslatableException(static function () use ($sut, $command): void {
            $sut->stage($command, self::activeDirPath(), self::stagingDirPath());
        }, InvalidArgumentException::class, $expectedExceptionMessage);
    }

    public static function providerCommandContainsWorkingDirOption(): array
    {
        return [
            'Full name' => [['--working-dir' => 'example/package']],
            'Short name' => [['-d' => 'example/package']],
        ];
    }

    public function testStagePreconditionsUnfulfilled(): void
    {
        $message = __METHOD__;
        $previous = self::createTestPreconditionException($message);
        $this->preconditions
            ->assertIsFulfilled(self::activeDirPath(), self::stagingDirPath(), Argument::cetera())
            ->shouldBeCalled()
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut): void {
            $sut->stage([self::INERT_COMMAND], self::activeDirPath(), self::stagingDirPath());
        }, PreconditionException::class, $previous->getTranslatableMessage());
    }

    #[DataProvider('providerExceptions')]
    public function testExceptions(ExceptionInterface $caughtException): void
    {
        $this->composerRunner
            ->run(Argument::cetera())
            ->willThrow($caughtException);
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut): void {
            $sut->stage([self::INERT_COMMAND], self::activeDirPath(), self::stagingDirPath());
        }, RuntimeException::class, $caughtException->getMessage(), null, $caughtException::class);
    }

    public static function providerExceptions(): array
    {
        return [
            'IOException' => [
                'caughtException' => new IOException(self::createTranslatableExceptionMessage('one')),
            ],
            'LogicException' => [
                'caughtException' => new LogicException(self::createTranslatableExceptionMessage('two')),
            ],
        ];
    }
}
