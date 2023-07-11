<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Core;

use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Exception\RuntimeException;
use PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\CommitterPreconditionsInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessRunnerInterface;
use PhpTuf\ComposerStager\Internal\Core\Committer;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPath;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPathList;
use PhpTuf\ComposerStager\Tests\Process\Service\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableExceptionMessage;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Core\Committer
 *
 * @covers \PhpTuf\ComposerStager\Internal\Core\Committer::__construct
 */
final class CommitterUnitTest extends TestCase
{
    private CommitterPreconditionsInterface|ObjectProphecy $preconditions;
    private FileSyncerInterface|ObjectProphecy $fileSyncer;

    protected function setUp(): void
    {
        $this->activeDir = new TestPath(self::ACTIVE_DIR_RELATIVE);
        $this->stagingDir = new TestPath(self::STAGING_DIR_RELATIVE);
        $this->preconditions = $this->prophesize(CommitterPreconditionsInterface::class);
        $this->fileSyncer = $this->prophesize(FileSyncerInterface::class);
    }

    private function createSut(): Committer
    {
        $preconditions = $this->preconditions->reveal();
        $fileSyncer = $this->fileSyncer->reveal();

        return new Committer($fileSyncer, $preconditions);
    }

    /** @covers ::commit */
    public function testCommitWithMinimumParams(): void
    {
        $this->preconditions
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, null)
            ->shouldBeCalledOnce();
        $this->fileSyncer
            ->sync($this->stagingDir, $this->activeDir, null, null, ProcessRunnerInterface::DEFAULT_TIMEOUT)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->commit($this->stagingDir, $this->activeDir);
    }

    /**
     * @covers ::commit
     *
     * @dataProvider providerCommitWithOptionalParams
     */
    public function testCommitWithOptionalParams(
        string $stagingDir,
        string $activeDir,
        ?PathListInterface $exclusions,
        ?ProcessOutputCallbackInterface $callback,
        ?int $timeout,
    ): void {
        $activeDir = new TestPath($activeDir);
        $stagingDir = new TestPath($stagingDir);
        $this->preconditions
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledOnce();
        $this->fileSyncer
            ->sync($stagingDir, $activeDir, $exclusions, $callback, $timeout)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->commit($stagingDir, $activeDir, $exclusions, $callback, $timeout);
    }

    public function providerCommitWithOptionalParams(): array
    {
        return [
            [
                'stagingDir' => '/one/two',
                'activeDir' => '/three/four',
                'exclusions' => null,
                'callback' => null,
                'timeout' => null,
            ],
            [
                'stagingDir' => 'five/six',
                'activeDir' => 'seven/eight',
                'exclusions' => new TestPathList(),
                'callback' => new TestProcessOutputCallback(),
                'timeout' => 10,
            ],
        ];
    }

    /** @covers ::commit */
    public function testCommitPreconditionsUnfulfilled(): void
    {
        $message = __METHOD__;
        $previous = self::createTestPreconditionException($message);
        $this->preconditions
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, Argument::cetera())
            ->shouldBeCalled()
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut): void {
            $sut->commit($this->stagingDir, $this->activeDir);
        }, PreconditionException::class, $previous->getTranslatableMessage());
    }

    /**
     * @covers ::commit
     *
     * @dataProvider providerExceptions
     */
    public function testExceptions(ExceptionInterface $exception, string $message): void
    {
        $this->fileSyncer
            ->sync($this->stagingDir, $this->activeDir, Argument::cetera())
            ->willThrow($exception);
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut): void {
            $sut->commit($this->stagingDir, $this->activeDir);
        }, RuntimeException::class, $message, $exception::class);
    }

    public function providerExceptions(): array
    {
        return [
            [
                'exception' => new InvalidArgumentException(new TestTranslatableExceptionMessage('one')),
                'message' => 'one',
            ],
            [
                'exception' => new IOException(new TestTranslatableExceptionMessage('two')),
                'message' => 'two',
            ],
        ];
    }
}
