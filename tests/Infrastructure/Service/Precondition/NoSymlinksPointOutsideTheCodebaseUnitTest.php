<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathListInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactoryInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinderInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoSymlinksPointOutsideTheCodebase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoSymlinksPointOutsideTheCodebase
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::getUnfulfilledStatusMessage
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractFileIteratingPrecondition
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\PathList
 *
 * @property \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactoryInterface|\Prophecy\Prophecy\ObjectProphecy $pathFactory
 * @property \PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinderInterface|\Prophecy\Prophecy\ObjectProphecy $fileFinder
 */
final class NoSymlinksPointOutsideTheCodebaseUnitTest extends FileIteratingPreconditionUnitTestCase
{
    protected function setUp(): void
    {
        $this->fileFinder = $this->prophesize(RecursiveFileFinderInterface::class);
        $this->fileFinder
            ->find(Argument::type(PathInterface::class), Argument::type(PathListInterface::class))
            ->willReturn([]);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->exists(Argument::type(PathInterface::class))
            ->willReturn(true);
        $this->pathFactory = $this->prophesize(PathFactoryInterface::class);

        parent::setUp();
    }

    protected function fulfilledStatusMessage(): string
    {
        return 'There are no symlinks that point outside the codebase.';
    }

    protected function createSut(): NoSymlinksPointOutsideTheCodebase
    {
        $fileFinder = $this->fileFinder->reveal();
        $filesystem = $this->filesystem->reveal();
        $pathFactory = $this->pathFactory->reveal();

        return new NoSymlinksPointOutsideTheCodebase($fileFinder, $filesystem, $pathFactory);
    }

    public function providerExceptions(): array
    {
        return [
            [new InvalidArgumentException('Exclusions include invalid paths.')],
            [new IOException('The directory cannot be found or is not actually a directory.')],
        ];
    }

    protected function assertFulfilledStatusMessage(
        bool $isFulfilled,
        string $statusMessage,
        string $assertionMessage,
    ): void {
        self::assertTrue($isFulfilled, $assertionMessage);
        self::assertSame('There are no symlinks that point outside the codebase.', $statusMessage, 'Got correct status message');
    }
}
