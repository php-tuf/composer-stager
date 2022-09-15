<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Core\Stager;

use PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\StagerPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Core\Stager\Stager;
use PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Exception\LogicException;
use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Exception\RuntimeException;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ComposerRunnerInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Service\ProcessOutputCallback\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Core\Stager\Stager
 *
 * @covers \PhpTuf\ComposerStager\Domain\Core\Stager\Stager
 *
 * @property \PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\StagerPreconditionsInterface|\Prophecy\Prophecy\ObjectProphecy $preconditions
 * @property \PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ComposerRunnerInterface|\Prophecy\Prophecy\ObjectProphecy $composerRunner
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 */
final class StagerUnitTest extends TestCase
{
    private const INERT_COMMAND = 'about';

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
        $this->composerRunner = $this->prophesize(ComposerRunnerInterface::class);
        $this->preconditions = $this->prophesize(StagerPreconditionsInterface::class);
    }

    protected function createSut(): Stager
    {
        $composerRunner = $this->composerRunner->reveal();
        $preconditions = $this->preconditions->reveal();

        return new Stager($composerRunner, $preconditions);
    }

    /** @dataProvider providerHappyPath */
    public function testHappyPath($givenCommand, $expectedCommand, $callback, $timeout): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->composerRunner
            ->run($expectedCommand, $callback, $timeout)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->stage($givenCommand, $activeDir, $stagingDir, $callback, $timeout);
    }

    public function providerHappyPath(): array
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

        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $sut = $this->createSut();

        $sut->stage([], $activeDir, $stagingDir);
    }

    public function testCommandContainsComposer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/cannot begin/');

        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $sut = $this->createSut();

        $sut->stage([
            'composer',
            self::INERT_COMMAND,
        ], $activeDir, $stagingDir);
    }

    /** @dataProvider providerCommandContainsWorkingDirOption */
    public function testCommandContainsWorkingDirOption($command): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/--working-dir/');

        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $sut = $this->createSut();

        $sut->stage($command, $activeDir, $stagingDir);
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

        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->preconditions
            ->assertIsFulfilled($this->activeDir, $this->stagingDir)
            ->shouldBeCalledOnce()
            ->willThrow(PreconditionException::class);
        $sut = $this->createSut();

        $sut->stage([self::INERT_COMMAND], $activeDir, $stagingDir);
    }

    /** @dataProvider providerExceptions */
    public function testExceptions($exception, $message): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->composerRunner
            ->run(Argument::cetera())
            ->willThrow($exception);

        $sut = $this->createSut();

        $sut->stage([self::INERT_COMMAND], $activeDir, $stagingDir);
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
