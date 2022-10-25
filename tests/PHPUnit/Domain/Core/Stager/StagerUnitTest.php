<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Core\Stager;

use PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\StagerPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Core\Stager\Stager;
use PhpTuf\ComposerStager\Domain\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Exception\LogicException;
use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Exception\RuntimeException;
use PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ComposerRunnerInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ProcessRunnerInterface;
use PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Service\ProcessOutputCallback\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Value\Path\TestPath;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Core\Stager\Stager
 *
 * @covers \PhpTuf\ComposerStager\Domain\Core\Stager\Stager
 *
 * @property \PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\StagerPreconditionsInterface|\Prophecy\Prophecy\ObjectProphecy $preconditions
 * @property \PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ComposerRunnerInterface|\Prophecy\Prophecy\ObjectProphecy $composerRunner
 * @property \PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Value\Path\TestPath $activeDir
 * @property \PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Value\Path\TestPath $stagingDir
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

    protected function createSut(): Stager
    {
        $composerRunner = $this->composerRunner->reveal();
        $preconditions = $this->preconditions->reveal();

        return new Stager($composerRunner, $preconditions);
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
        ?int $timeout
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
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/empty/');

        $sut = $this->createSut();

        $sut->stage([], $this->activeDir, $this->stagingDir);
    }

    public function testCommandContainsComposer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/cannot begin/');

        $sut = $this->createSut();

        $sut->stage([
            'composer',
            self::INERT_COMMAND,
        ], $this->activeDir, $this->stagingDir);
    }

    /** @dataProvider providerCommandContainsWorkingDirOption */
    public function testCommandContainsWorkingDirOption(array $command): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/--working-dir/');

        $sut = $this->createSut();

        $sut->stage($command, $this->activeDir, $this->stagingDir);
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
        $this->expectException(PreconditionException::class);

        $this->preconditions
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, Argument::cetera())
            ->shouldBeCalledOnce()
            ->willThrow(PreconditionException::class);
        $sut = $this->createSut();

        $sut->stage([self::INERT_COMMAND], $this->activeDir, $this->stagingDir);
    }

    /** @dataProvider providerExceptions */
    public function testExceptions(ExceptionInterface $exception, string $message): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        $this->composerRunner
            ->run(Argument::cetera())
            ->willThrow($exception);

        $sut = $this->createSut();

        $sut->stage([self::INERT_COMMAND], $this->activeDir, $this->stagingDir);
    }

    public function providerExceptions(): array
    {
        return [
            [
                'exception' => new IOException('one'),
                'message' => 'one',
            ],
            [
                'exception' => new LogicException('two'),
                'message' => 'two',
            ],
        ];
    }
}
