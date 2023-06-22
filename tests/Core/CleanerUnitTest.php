<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Core;

use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Exception\RuntimeException;
use PhpTuf\ComposerStager\API\Precondition\Service\CleanerPreconditionsInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessRunnerInterface;
use PhpTuf\ComposerStager\Internal\Core\Cleaner;
use PhpTuf\ComposerStager\Internal\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPath;
use PhpTuf\ComposerStager\Tests\Process\Service\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableExceptionMessage;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Core\Cleaner
 *
 * @covers \PhpTuf\ComposerStager\Internal\Core\Cleaner::__construct
 *
 * @uses \PhpTuf\ComposerStager\API\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\API\Exception\TranslatableExceptionTrait
 *
 * @property \PhpTuf\ComposerStager\API\Precondition\Service\CleanerPreconditionsInterface|\Prophecy\Prophecy\ObjectProphecy $preconditions
 * @property \PhpTuf\ComposerStager\Internal\Filesystem\Service\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Tests\Path\Value\TestPath $activeDir
 * @property \PhpTuf\ComposerStager\Tests\Path\Value\TestPath $stagingDir
 */
final class CleanerUnitTest extends TestCase
{
    public function setUp(): void
    {
        $this->activeDir = new TestPath(self::ACTIVE_DIR);
        $this->stagingDir = new TestPath(self::STAGING_DIR);
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
        $this->preconditions
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, null)
            ->shouldBeCalledOnce();
        $this->filesystem
            ->remove($this->stagingDir, null, ProcessRunnerInterface::DEFAULT_TIMEOUT)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->clean($this->activeDir, $this->stagingDir);
    }

    /**
     * @covers ::clean
     *
     * @dataProvider providerCleanWithOptionalParams
     */
    public function testCleanWithOptionalParams(
        string $path,
        ?ProcessOutputCallbackInterface $callback,
        ?int $timeout,
    ): void {
        $path = new TestPath($path);
        $this->preconditions
            ->assertIsFulfilled($this->activeDir, $path)
            ->shouldBeCalledOnce();
        $this->filesystem
            ->remove($path, $callback, $timeout)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->clean($this->activeDir, $path, $callback, $timeout);
    }

    public function providerCleanWithOptionalParams(): array
    {
        return [
            [
                'path' => '/one/two',
                'callback' => null,
                'timeout' => null,
            ],
            [
                'path' => 'three/four',
                'callback' => new TestProcessOutputCallback(),
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
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, Argument::cetera())
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut) {
            $sut->clean($this->activeDir, $this->stagingDir);
        }, PreconditionException::class, $previous->getTranslatableMessage());
    }

    /** @covers ::clean */
    public function testCleanFailToRemove(): void
    {
        $message = new TestTranslatableExceptionMessage(__METHOD__);
        $previous = new IOException($message);
        $this->filesystem
            ->remove(Argument::cetera())
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut) {
            $sut->clean($this->activeDir, $this->stagingDir);
        }, RuntimeException::class, $message, $previous::class);
    }
}
