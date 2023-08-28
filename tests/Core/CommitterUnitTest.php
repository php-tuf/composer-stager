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
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\Internal\Core\Committer;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPath;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPathList;
use PhpTuf\ComposerStager\Tests\Process\Service\TestOutputCallback;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
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
        $timeout = ProcessInterface::DEFAULT_TIMEOUT;

        $this->preconditions
            ->assertIsFulfilled(PathHelper::activeDirPath(), PathHelper::stagingDirPath(), null, $timeout)
            ->shouldBeCalledOnce();
        $this->fileSyncer
            ->sync(PathHelper::stagingDirPath(), PathHelper::activeDirPath(), null, null, $timeout)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->commit(PathHelper::stagingDirPath(), PathHelper::activeDirPath());
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
        ?OutputCallbackInterface $callback,
        int $timeout,
    ): void {
        $activeDir = new TestPath($activeDir);
        $stagingDir = new TestPath($stagingDir);
        $this->preconditions
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions, $timeout)
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
            'Minimum values' => [
                'stagingDir' => '/one/two',
                'activeDir' => '/three/four',
                'exclusions' => null,
                'callback' => null,
                'timeout' => 0,
            ],
            'Simple values' => [
                'stagingDir' => 'five/six',
                'activeDir' => 'seven/eight',
                'exclusions' => new TestPathList(),
                'callback' => new TestOutputCallback(),
                'timeout' => 10,
            ],
        ];
    }

    /** @covers ::commit */
    public function testCommitPreconditionsUnfulfilled(): void
    {
        $activeDirPath = PathHelper::activeDirPath();
        $stagingDirPath = PathHelper::stagingDirPath();

        $message = __METHOD__;
        $previous = self::createTestPreconditionException($message);
        $this->preconditions
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, Argument::cetera())
            ->shouldBeCalled()
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut, $activeDirPath, $stagingDirPath): void {
            $sut->commit($stagingDirPath, $activeDirPath);
        }, PreconditionException::class, $previous->getTranslatableMessage());
    }

    /**
     * @covers ::commit
     *
     * @dataProvider providerExceptions
     */
    public function testExceptions(ExceptionInterface $exception, string $message): void
    {
        $stagingDirPath = PathHelper::stagingDirPath();
        $activeDirPath = PathHelper::activeDirPath();

        $this->fileSyncer
            ->sync($stagingDirPath, $activeDirPath, Argument::cetera())
            ->willThrow($exception);
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut, $activeDirPath, $stagingDirPath): void {
            $sut->commit($stagingDirPath, $activeDirPath);
        }, RuntimeException::class, $message, $exception::class);
    }

    public function providerExceptions(): array
    {
        return [
            'InvalidArgumentException' => [
                'exception' => new InvalidArgumentException(new TestTranslatableExceptionMessage('one')),
                'message' => 'one',
            ],
            'IOException' => [
                'exception' => new IOException(new TestTranslatableExceptionMessage('two')),
                'message' => 'two',
            ],
        ];
    }
}
