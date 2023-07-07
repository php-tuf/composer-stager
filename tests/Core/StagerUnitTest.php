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
use PhpTuf\ComposerStager\API\Process\Service\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessRunnerInterface;
use PhpTuf\ComposerStager\Internal\Core\Stager;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPath;
use PhpTuf\ComposerStager\Tests\Process\Service\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\TestCase;
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
        $this->activeDir = new TestPath(self::ACTIVE_DIR);
        $this->stagingDir = new TestPath(self::STAGING_DIR);
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
        $this->preconditions
            ->assertIsFulfilled($this->activeDir, $this->stagingDir)
            ->shouldBeCalledOnce();
        $expectedCommand = [
            '--working-dir=' . self::STAGING_DIR,
            self::INERT_COMMAND,
        ];
        $this->composerRunner
            ->run($expectedCommand, null, ProcessRunnerInterface::DEFAULT_TIMEOUT)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->stage([self::INERT_COMMAND], $this->activeDir, $this->stagingDir);
    }

    /** @dataProvider providerStageWithOptionalParams */
    public function testStageWithOptionalParams(
        array $givenCommand,
        array $expectedCommand,
        ?ProcessOutputCallbackInterface $callback,
        ?int $timeout,
    ): void {
        $this->preconditions
            ->assertIsFulfilled($this->activeDir, $this->stagingDir)
            ->shouldBeCalledOnce();
        $this->composerRunner
            ->run($expectedCommand, $callback, $timeout)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->stage($givenCommand, $this->activeDir, $this->stagingDir, $callback, $timeout);
    }

    public function providerStageWithOptionalParams(): array
    {
        return [
            [
                'givenCommand' => ['update'],
                'expectedCommand' => [
                    '--working-dir=' . self::STAGING_DIR,
                    'update',
                ],
                'callback' => null,
                'timeout' => null,
            ],
            [
                'givenCommand' => [self::INERT_COMMAND],
                'expectedCommand' => [
                    '--working-dir=' . self::STAGING_DIR,
                    self::INERT_COMMAND,
                ],
                'callback' => new TestProcessOutputCallback(),
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

        self::assertTranslatableException(function () use ($sut) {
            $sut->stage([], $this->activeDir, $this->stagingDir);
        }, InvalidArgumentException::class, $expectedExceptionMessage);
    }

    /** @covers ::validateCommand */
    public function testCommandContainsComposer(): void
    {
        $sut = $this->createSut();

        $expectedExceptionMessage = new TestTranslatableMessage(
            'The Composer command cannot begin with "composer"--it is implied',
            null,
            self::DOMAIN_EXCEPTIONS,
        );
        self::assertTranslatableException(function () use ($sut) {
            $sut->stage([
                'composer',
                self::INERT_COMMAND,
            ], $this->activeDir, $this->stagingDir);
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
            self::DOMAIN_EXCEPTIONS,
        );
        self::assertTranslatableException(function () use ($sut, $command) {
            $sut->stage($command, $this->activeDir, $this->stagingDir);
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
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, Argument::cetera())
            ->shouldBeCalled()
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut) {
            $sut->stage([self::INERT_COMMAND], $this->activeDir, $this->stagingDir);
        }, PreconditionException::class, $previous->getTranslatableMessage());
    }

    /** @dataProvider providerExceptions */
    public function testExceptions(ExceptionInterface $exception, string $message): void
    {
        $this->composerRunner
            ->run(Argument::cetera())
            ->willThrow($exception);
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut) {
            $sut->stage([self::INERT_COMMAND], $this->activeDir, $this->stagingDir);
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
