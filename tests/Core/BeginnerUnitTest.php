<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Core;

use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Exception\RuntimeException;
use PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\BeginnerPreconditionsInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\Internal\Core\Beginner;
use PhpTuf\ComposerStager\Internal\Process\Service\OutputCallback;
use PhpTuf\ComposerStager\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

#[CoversClass(Beginner::class)]
final class BeginnerUnitTest extends TestCase
{
    private BeginnerPreconditionsInterface|ObjectProphecy $preconditions;
    private FileSyncerInterface|ObjectProphecy $fileSyncer;

    protected function setUp(): void
    {
        $this->preconditions = $this->prophesize(BeginnerPreconditionsInterface::class);
        $this->fileSyncer = $this->prophesize(FileSyncerInterface::class);
    }

    private function createSut(): Beginner
    {
        $fileSyncer = $this->fileSyncer->reveal();
        $preconditions = $this->preconditions->reveal();

        return new Beginner($fileSyncer, $preconditions);
    }

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

    #[DataProvider('providerBeginWithOptionalParams')]
    public function testBeginWithOptionalParams(
        string $activeDir,
        string $stagingDir,
        ?PathListInterface $givenExclusions,
        ?OutputCallbackInterface $callback,
        int $timeout,
    ): void {
        $activeDir = self::createPath($activeDir);
        $stagingDir = self::createPath($stagingDir);
        $this->preconditions
            ->assertIsFulfilled($activeDir, $stagingDir, $givenExclusions, $timeout)
            ->shouldBeCalledOnce();
        $this->fileSyncer
            ->sync($activeDir, $stagingDir, $givenExclusions, $callback, $timeout)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->begin($activeDir, $stagingDir, $givenExclusions, $callback, $timeout);
    }

    public static function providerBeginWithOptionalParams(): array
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
                'givenExclusions' => self::createPathList(),
                'callback' => new OutputCallback(),
                'timeout' => 100,
            ],
        ];
    }

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

    #[DataProvider('providerExceptions')]
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

    public static function providerExceptions(): array
    {
        return [
            'InvalidArgumentException' => [
                'caughtException' => new InvalidArgumentException(
                    self::createTranslatableExceptionMessage('one'),
                ),
            ],
            'IOException' => [
                'caughtException' => new IOException(
                    self::createTranslatableExceptionMessage('two'),
                ),
            ],
        ];
    }
}
