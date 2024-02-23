<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Core;

use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Exception\RuntimeException;
use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\CleanerPreconditionsInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\Internal\Core\Cleaner;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestDoubles\Process\Service\TestOutputCallback;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Core\Cleaner
 *
 * @covers \PhpTuf\ComposerStager\Internal\Core\Cleaner::__construct
 */
final class CleanerUnitTest extends TestCase
{
    private CleanerPreconditionsInterface|ObjectProphecy $preconditions;
    private FilesystemInterface|ObjectProphecy $filesystem;

    protected function setUp(): void
    {
        $this->preconditions = $this->prophesize(CleanerPreconditionsInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
    }

    private function createSut(): Cleaner
    {
        $filesystem = $this->filesystem->reveal();
        $preconditions = $this->preconditions->reveal();

        return new Cleaner($filesystem, $preconditions);
    }

    /** @covers ::clean */
    public function testCleanWithMinimumParams(): void
    {
        $timeout = ProcessInterface::DEFAULT_TIMEOUT;

        $this->preconditions
            ->assertIsFulfilled(self::activeDirPath(), self::stagingDirPath(), null, $timeout)
            ->shouldBeCalledOnce();
        $this->filesystem
            ->rm(self::stagingDirPath(), null, $timeout)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->clean(self::activeDirPath(), self::stagingDirPath());
    }

    /**
     * @covers ::clean
     *
     * @dataProvider providerCleanWithOptionalParams
     */
    public function testCleanWithOptionalParams(string $path, ?OutputCallbackInterface $callback, int $timeout): void
    {
        $path = self::createPath($path);
        $this->preconditions
            ->assertIsFulfilled(self::activeDirPath(), $path, null, $timeout)
            ->shouldBeCalledOnce();
        $this->filesystem
            ->rm($path, $callback, $timeout)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->clean(self::activeDirPath(), $path, $callback, $timeout);
    }

    public function providerCleanWithOptionalParams(): array
    {
        return [
            'Minimum values' => [
                'path' => '/one/two',
                'callback' => null,
                'timeout' => 0,
            ],
            'Simple values' => [
                'path' => 'three/four',
                'callback' => new TestOutputCallback(),
                'timeout' => 10,
            ],
        ];
    }

    /** @covers ::clean */
    public function testCleanPreconditionsUnfulfilled(): void
    {
        $message = __METHOD__;
        $previous = self::createTestPreconditionException($message);
        $this->preconditions
            ->assertIsFulfilled(self::activeDirPath(), self::stagingDirPath(), Argument::cetera())
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut): void {
            $sut->clean(self::activeDirPath(), self::stagingDirPath());
        }, PreconditionException::class, $previous->getTranslatableMessage());
    }

    /** @covers ::clean */
    public function testCleanFailToRemove(): void
    {
        $message = self::createTranslatableExceptionMessage(__METHOD__);
        $previous = new IOException($message);
        $this->filesystem
            ->rm(Argument::cetera())
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut): void {
            $sut->clean(self::activeDirPath(), self::stagingDirPath());
        }, RuntimeException::class, $message, null, $previous::class);
    }
}
