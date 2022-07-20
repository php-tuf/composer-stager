<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinderInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\CodeBaseContainsNoSymlinks;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\CodeBaseContainsNoSymlinks
 *
 * @covers ::__construct
 *
 * @property \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 * @property \PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinder|\Prophecy\Prophecy\ObjectProphecy $fileFinder
 */
final class CodeBaseContainsNoSymlinksUnitTest extends TestCase
{
    protected function setUp(): void
    {
        $this->activeDir = $this->prophesize(PathInterface::class);
        $this->stagingDir = $this->prophesize(PathInterface::class);
        $this->fileFinder = $this->prophesize(RecursiveFileFinderInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->exists(Argument::any())
            ->willReturn(false);
    }

    private function createSut(): CodeBaseContainsNoSymlinks
    {
        $fileFinder = $this->fileFinder->reveal();
        $filesystem = $this->filesystem->reveal();

        return new CodeBaseContainsNoSymlinks($fileFinder, $filesystem);
    }

    /**
     * @covers ::findFiles
     * @covers ::isFulfilled
     */
    public function testNoFilesFound(): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->fileFinder
            ->find(Argument::cetera())
            ->willReturn([]);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDir, $stagingDir);

        self::assertTrue($isFulfilled, 'Treated empty codebase as fulfilled.');
    }

    /**
     * @covers ::findFiles
     * @covers ::getUnfulfilledStatusMessage
     * @covers ::isFulfilled
     *
     * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition::getStatusMessage
     *
     * @dataProvider providerFinderError
     */
    public function testFinderException($exception, $expectedMessage): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->fileFinder
            ->find(Argument::cetera())
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
