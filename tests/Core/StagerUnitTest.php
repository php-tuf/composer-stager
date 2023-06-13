<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Core;

use PhpTuf\ComposerStager\Domain\Core\Stager;
use PhpTuf\ComposerStager\Domain\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Exception\LogicException;
use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Exception\RuntimeException;
use PhpTuf\ComposerStager\Domain\Precondition\Service\StagerPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\ProcessOutputCallback\Service\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\ProcessRunner\Service\ComposerRunnerInterface;
use PhpTuf\ComposerStager\Domain\ProcessRunner\Service\ProcessRunnerInterface;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPath;
use PhpTuf\ComposerStager\Tests\ProcessOutputCallback\Service\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableMessage;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Core\Stager
 *
 * @covers \PhpTuf\ComposerStager\Domain\Core\Stager
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 *
 * @property \PhpTuf\ComposerStager\Domain\Precondition\Service\StagerPreconditionsInterface|\Prophecy\Prophecy\ObjectProphecy $preconditions
 * @property \PhpTuf\ComposerStager\Domain\ProcessRunner\Service\ComposerRunnerInterface|\Prophecy\Prophecy\ObjectProphecy $composerRunner
 * @property \PhpTuf\ComposerStager\Tests\Path\Value\TestPath $activeDir
 * @property \PhpTuf\ComposerStager\Tests\Path\Value\TestPath $stagingDir
 */
final class StagerUnitTest extends TestCase
{
    private const INERT_COMMAND = 'about';

    protected function setUp(): void
    {
        $this->activeDir = new TestPath(self::ACTIVE_DIR);
        $this->stagingDir = new TestPath(self::STAGING_DIR);
        $this->composerRunner = $this->prophesize(ComposerRunnerInterface::class);
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

    public function testEmptyCommand(): void
    {
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut) {
            $sut->stage([], $this->activeDir, $this->stagingDir);
        }, InvalidArgumentException::class, 'The Composer command cannot be empty');
    }

    public function testCommandContainsComposer(): void
    {
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut) {
            $sut->stage([
                'composer',
                self::INERT_COMMAND,
            ], $this->activeDir, $this->stagingDir);
        }, InvalidArgumentException::class, 'The Composer command cannot begin with "composer"--it is implied');
    }

    /** @dataProvider providerCommandContainsWorkingDirOption */
    public function testCommandContainsWorkingDirOption(array $command): void
    {
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut, $command) {
            $sut->stage($command, $this->activeDir, $this->stagingDir);
        }, InvalidArgumentException::class, 'Cannot stage a Composer command containing the "--working-dir" (or "-d") option');
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
        }, PreconditionException::class, $message);
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
                'exception' => new IOException(new TestTranslatableMessage('one')),
                'message' => 'one',
            ],
            [
                'exception' => new LogicException(new TestTranslatableMessage('two')),
                'message' => 'two',
            ],
        ];
    }
}
