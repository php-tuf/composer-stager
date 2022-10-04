<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Core\Beginner;

use PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\BeginnerPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Core\Beginner\Beginner;
use PhpTuf\ComposerStager\Domain\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Exception\RuntimeException;
use PhpTuf\ComposerStager\Domain\Service\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ProcessRunnerInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;
use PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Service\ProcessOutputCallback\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Core\Beginner\Beginner
 *
 * @covers \PhpTuf\ComposerStager\Domain\Core\Beginner\Beginner::__construct
 *
 * @property \PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\BeginnerPreconditionsInterface|\Prophecy\Prophecy\ObjectProphecy $preconditions
 * @property \PhpTuf\ComposerStager\Domain\Service\FileSyncer\FileSyncerInterface|\Prophecy\Prophecy\ObjectProphecy $fileSyncer
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 */
final class BeginnerUnitTest extends TestCase
{
    protected function setUp(): void
    {
        $this->activeDir = $this->prophesize(PathInterface::class);
        $this->activeDir
            ->resolve()
            ->willReturn(self::ACTIVE_DIR);
        $this->stagingDir = $this->prophesize(PathInterface::class);
        $this->stagingDir
            ->resolve()
            ->willReturn(self::STAGING_DIR);
        $this->preconditions = $this->prophesize(BeginnerPreconditionsInterface::class);
        $this->fileSyncer = $this->prophesize(FileSyncerInterface::class);
    }

    protected function createSut(): Beginner
    {
        $fileSyncer = $this->fileSyncer->reveal();
        $preconditions = $this->preconditions->reveal();

        return new Beginner($fileSyncer, $preconditions);
    }

    /** @covers ::begin */
    public function testBeginWithMinimumParams(): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        /** @noinspection PhpRedundantOptionalArgumentInspection */
        $this->fileSyncer
            ->sync($activeDir, $stagingDir, null, null, ProcessRunnerInterface::DEFAULT_TIMEOUT)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->begin($activeDir, $stagingDir);
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
        ?int $timeout
    ): void {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
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
                'givenExclusions' => $this->prophesize(PathListInterface::class)->reveal(),
                'callback' => new TestProcessOutputCallback(),
                'timeout' => 100,
            ],
        ];
    }

    /** @covers ::begin */
    public function testBeginPreconditionsUnfulfilled(): void
    {
        $this->expectException(PreconditionException::class);

        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->preconditions
            ->assertIsFulfilled($activeDir, $stagingDir)
            ->shouldBeCalledOnce()
            ->willThrow(PreconditionException::class);
        $sut = $this->createSut();

        $sut->begin($activeDir, $stagingDir);
    }

    /**
     * @covers ::begin
     *
     * @dataProvider providerExceptions
     */
    public function testExceptions(ExceptionInterface $exception, string $message): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        /** @noinspection PhpParamsInspection */
        $this->fileSyncer
            ->sync(Argument::cetera())
            ->willThrow($exception);
        $sut = $this->createSut();

        $sut->begin($activeDir, $stagingDir);
    }

    public function providerExceptions(): array
    {
        return [
            [
                'exception' => new InvalidArgumentException('one'),
                'message' => 'one',
            ],
            [
                'exception' => new IOException('two'),
                'message' => 'two',
            ],
        ];
    }
}
