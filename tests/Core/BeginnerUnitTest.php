<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Core;

use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Exception\RuntimeException;
use PhpTuf\ComposerStager\API\FileSyncer\Factory\FileSyncerFactoryInterface;
use PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\BeginnerPreconditionsInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\Internal\Core\Beginner;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestDoubles\Path\Value\TestPathList;
use PhpTuf\ComposerStager\Tests\TestDoubles\Process\Service\TestOutputCallback;
use PhpTuf\ComposerStager\Tests\TestUtils\TranslationTestHelper;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Core\Beginner
 *
 * @covers \PhpTuf\ComposerStager\Internal\Core\Beginner::__construct
 */
final class BeginnerUnitTest extends TestCase
{
    private BeginnerPreconditionsInterface|ObjectProphecy $preconditions;
    private FileSyncerInterface|ObjectProphecy $fileSyncer;
    private FileSyncerFactoryInterface|ObjectProphecy $fileSyncerFactory;

    protected function setUp(): void
    {
        $this->preconditions = $this->prophesize(BeginnerPreconditionsInterface::class);
        $this->fileSyncer = $this->prophesize(FileSyncerInterface::class);
        $this->fileSyncerFactory = $this->prophesize(FileSyncerFactoryInterface::class);
    }

    private function createSut(): Beginner
    {
        $this->fileSyncerFactory
            ->create()
            ->willReturn($this->fileSyncer->reveal());
        $fileSyncerFactory = $this->fileSyncerFactory->reveal();
        $preconditions = $this->preconditions->reveal();

        return new Beginner($fileSyncerFactory, $preconditions);
    }

    /** @covers ::begin */
    public function testBeginWithMinimumParams(): void
    {
        $timeout = ProcessInterface::DEFAULT_TIMEOUT;

        $this->preconditions
            ->assertIsFulfilled(self::activeDirPath(), self::stagingDirPath(), null, $timeout)
            ->shouldBeCalledOnce();
        $this->fileSyncer
            ->sync(self::activeDirPath(), self::stagingDirPath(), null, null, $timeout)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->begin(self::activeDirPath(), self::stagingDirPath());
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
        ?OutputCallbackInterface $callback,
        int $timeout,
    ): void {
        $activeDir = self::createPath($activeDir);
        $stagingDir = self::createPath($stagingDir);
        $this->preconditions
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions, $timeout)
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
            'Minimum values' => [
                'activeDir' => 'one/two',
                'stagingDir' => 'three/four',
                'givenExclusions' => null,
                'callback' => null,
                'timeout' => 0,
            ],
            'Simple values' => [
                'activeDir' => 'five/six',
                'stagingDir' => 'seven/eight',
                'givenExclusions' => new TestPathList(),
                'callback' => new TestOutputCallback(),
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
            ->assertIsFulfilled(self::activeDirPath(), self::stagingDirPath(), Argument::cetera())
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut): void {
            $sut->begin(self::activeDirPath(), self::stagingDirPath());
        }, PreconditionException::class, $previous->getTranslatableMessage());
    }

    /**
     * @covers ::begin
     *
     * @dataProvider providerExceptions
     */
    public function testExceptions(ExceptionInterface $caughtException): void
    {
        $this->fileSyncer
            ->sync(Argument::cetera())
            ->willThrow($caughtException);
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut): void {
            $sut->begin(self::activeDirPath(), self::stagingDirPath());
        }, RuntimeException::class, $caughtException->getMessage(), null, $caughtException::class);
    }

    public function providerExceptions(): array
    {
        return [
            'InvalidArgumentException' => [
                'caughtException' => new InvalidArgumentException(
                    TranslationTestHelper::createTranslatableExceptionMessage('one'),
                ),
            ],
            'IOException' => [
                'caughtException' => new IOException(
                    TranslationTestHelper::createTranslatableExceptionMessage('two'),
                ),
            ],
        ];
    }
}
