<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinderInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\CodeBaseContainsNoSymlinks;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\CodeBaseContainsNoSymlinks
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::isFulfilled
 *
 * @property \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 * @property \PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinderInterface|\Prophecy\Prophecy\ObjectProphecy $fileFinder
 */
final class CodeBaseContainsNoSymlinksUnitTest extends PreconditionTestCase
{
    protected function setUp(): void
    {
        $this->fileFinder = $this->prophesize(RecursiveFileFinderInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->exists(Argument::type(PathInterface::class))
            ->willReturn(true);

        parent::setUp();
    }

    protected function createSut(): CodeBaseContainsNoSymlinks
    {
        $fileFinder = $this->fileFinder->reveal();
        $filesystem = $this->filesystem->reveal();

        return new CodeBaseContainsNoSymlinks($fileFinder, $filesystem);
    }

    /** @covers ::findFiles */
    public function testNoFilesFound(): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->fileFinder
            ->find(Argument::type(PathInterface::class))
            ->willReturn([]);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDir, $stagingDir);

        self::assertTrue($isFulfilled, 'Treated empty codebase as fulfilled.');
    }

    /**
     * @covers ::findFiles
     *
     * @dataProvider providerDirectoryNotFound
     */
    public function testDirectoryNotFound(bool $activeDirExists, bool $stagingDirExists): void
    {
        // Double expectations: once for ::isFulfilled() and once for ::assertIsFulfilled().
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->filesystem
            ->exists($activeDir)
            ->shouldBeCalledTimes(2)
            ->willReturn($activeDirExists);
        $this->filesystem
            ->exists($stagingDir)
            ->shouldBeCalledTimes(2)
            ->willReturn($stagingDirExists);
        $this->fileFinder
            ->find($activeDir)
            ->shouldBeCalledTimes(2 * (int) $activeDirExists);
        $this->fileFinder
            ->find($stagingDir)
            ->shouldBeCalledTimes(2 * (int) $stagingDirExists);

        $this->testFulfilled();
    }

    public function providerDirectoryNotFound(): array
    {
        return [
            'Active directory not found' => [
                'activeDirExists' => false,
                'stagingDirExists' => true,
            ],
            'Staging directory not found' => [
                'activeDirExists' => true,
                'stagingDirExists' => false,
            ],
        ];
    }

    /**
     * @covers ::findFiles
     * @covers ::getUnfulfilledStatusMessage
     *
     * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition::getStatusMessage
     *
     * @dataProvider providerFinderError
     */
    public function testFinderException(ExceptionInterface $exception, string $expectedMessage): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->filesystem
            ->exists(Argument::type(PathInterface::class))
            ->willReturn(true);
        $this->fileFinder
            ->find(Argument::type(PathInterface::class))
            ->willThrow($exception);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDir, $stagingDir);
        $actualMessage = $sut->getStatusMessage($activeDir, $stagingDir);

        self::assertFalse($isFulfilled, 'Treated finder error as unfulfilled.');
        self::assertSame($expectedMessage, $actualMessage, 'Returned correct status message');
    }

    public function providerFinderError(): array
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
