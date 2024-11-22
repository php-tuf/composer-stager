<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\API\Finder\Service\FileFinderInterface;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoSymlinksPointOutsideTheCodebase;
use PHPUnit\Framework\Attributes\CoversClass;
use Prophecy\Argument;

#[CoversClass(NoSymlinksPointOutsideTheCodebase::class)]
final class NoSymlinksPointOutsideTheCodebaseUnitTest extends FileIteratingPreconditionUnitTestCase
{
    protected const NAME = 'No symlinks point outside the codebase';
    protected const DESCRIPTION = 'The codebase cannot contain symlinks that point outside the codebase.';
    protected const FULFILLED_STATUS_MESSAGE = 'There are no symlinks that point outside the codebase.';

    protected function setUp(): void
    {
        $this->fileFinder = $this->prophesize(FileFinderInterface::class);
        $this->fileFinder
            ->find(Argument::type(PathInterface::class), Argument::type(PathListInterface::class))
            ->willReturn([]);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->fileExists(Argument::type(PathInterface::class))
            ->willReturn(true);
        $this->pathFactory = $this->prophesize(PathFactoryInterface::class);

        parent::setUp();
    }

    protected function createSut(): NoSymlinksPointOutsideTheCodebase
    {
        $environment = $this->environment->reveal();
        $fileFinder = $this->fileFinder->reveal();
        $filesystem = $this->filesystem->reveal();
        $pathFactory = $this->pathFactory->reveal();
        $pathHelper = self::createPathHelper();
        $pathListFactory = self::createPathListFactory();
        $translatableFactory = self::createTranslatableFactory();

        return new NoSymlinksPointOutsideTheCodebase($environment, $fileFinder, $filesystem, $pathFactory, $pathHelper, $pathListFactory, $translatableFactory);
    }
}
