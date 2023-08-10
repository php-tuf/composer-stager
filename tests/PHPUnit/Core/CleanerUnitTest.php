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
use PhpTuf\ComposerStager\Tests\Path\Value\TestPath;
use PhpTuf\ComposerStager\Tests\Process\Service\TestOutputCallback;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableExceptionMessage;
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
        $this->activeDir = new TestPath(PathHelper::activeDirRelative());
        $this->stagingDir = new TestPath(self::STAGING_DIR_RELATIVE);
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
            ->remove($this->stagingDir, null, ProcessInterface::DEFAULT_TIMEOUT)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->clean($this->activeDir, $this->stagingDir);
    }

    /**
     * @covers ::clean
     *
     * @dataProvider providerCleanWithOptionalParams
     */
    public function testCleanWithOptionalParams(string $path, ?OutputCallbackInterface $callback, ?int $timeout): void
    {
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
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, Argument::cetera())
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut): void {
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

        self::assertTranslatableException(function () use ($sut): void {
            $sut->clean($this->activeDir, $this->stagingDir);
        }, RuntimeException::class, $message, $previous::class);
    }
}
