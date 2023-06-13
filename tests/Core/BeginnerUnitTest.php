<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Core;

use PhpTuf\ComposerStager\API\Core\Beginner;
use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Exception\RuntimeException;
use PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\BeginnerPreconditionsInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessRunnerInterface;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPath;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPathList;
use PhpTuf\ComposerStager\Tests\Process\Service\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableMessage;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\API\Core\Beginner
 *
 * @covers \PhpTuf\ComposerStager\API\Core\Beginner::__construct
 *
 * @uses \PhpTuf\ComposerStager\API\Exception\PreconditionException
 *
 * @property \PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface|\Prophecy\Prophecy\ObjectProphecy $fileSyncer
 * @property \PhpTuf\ComposerStager\API\Precondition\Service\BeginnerPreconditionsInterface|\Prophecy\Prophecy\ObjectProphecy $preconditions
 * @property \PhpTuf\ComposerStager\Tests\Path\Value\TestPath $activeDir
 * @property \PhpTuf\ComposerStager\Tests\Path\Value\TestPath $stagingDir
 */
final class BeginnerUnitTest extends TestCase
{
    protected function setUp(): void
    {
        $this->activeDir = new TestPath(self::ACTIVE_DIR);
        $this->stagingDir = new TestPath(self::STAGING_DIR);
        $this->preconditions = $this->prophesize(BeginnerPreconditionsInterface::class);
        $this->fileSyncer = $this->prophesize(FileSyncerInterface::class);
    }

    private function createSut(): Beginner
    {
        $fileSyncer = $this->fileSyncer->reveal();
        $preconditions = $this->preconditions->reveal();

        return new Beginner($fileSyncer, $preconditions);
    }

    /** @covers ::begin */
    public function testBeginWithMinimumParams(): void
    {
        $this->preconditions
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, null)
            ->shouldBeCalledOnce();
        $this->fileSyncer
            ->sync($this->activeDir, $this->stagingDir, null, null, ProcessRunnerInterface::DEFAULT_TIMEOUT)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->begin($this->activeDir, $this->stagingDir);
    }

    /**
     * @covers ::begin
     *
     * @dataProvider providerBeginWithOptionalParams
     */
    public function testBeginWithOptionalParams(
        string $activeDir,
        string $stagingDir,
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
            ->sync($activeDir, $stagingDir, $exclusions, $callback, $timeout)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->begin($activeDir, $stagingDir, $exclusions, $callback, $timeout);
    }

    public function providerBeginWithOptionalParams(): array
    {
        return [
            [
                'activeDir' => 'one/two',
                'stagingDir' => 'three/four',
                'givenExclusions' => null,
                'callback' => null,
                'timeout' => null,
            ],
            [
                'activeDir' => 'five/six',
                'stagingDir' => 'seven/eight',
                'givenExclusions' => new TestPathList(),
                'callback' => new TestProcessOutputCallback(),
                'timeout' => 100,
            ],
        ];
    }

    /** @covers ::begin */
    public function testBeginPreconditionsUnfulfilled(): void
    {
        $message = __METHOD__;
        $previous = self::createTestPreconditionException($message);
        $this->preconditions
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, Argument::cetera())
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut) {
            $sut->begin($this->activeDir, $this->stagingDir);
        }, PreconditionException::class, $message);
    }

    /**
     * @covers ::begin
     *
     * @dataProvider providerExceptions
     */
    public function testExceptions(ExceptionInterface $exception): void
    {
        $this->fileSyncer
            ->sync(Argument::cetera())
            ->willThrow($exception);
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut) {
            $sut->begin($this->activeDir, $this->stagingDir);
        }, RuntimeException::class, $exception->getMessage(), $exception::class);
    }

    public function providerExceptions(): array
    {
        return [
            [
                'exception' => new InvalidArgumentException(
                    new TestTranslatableMessage('one'),
                ),
            ],
            [
                'exception' => new IOException(
                    new TestTranslatableMessage('two'),
                ),
            ],
        ];
    }
}
