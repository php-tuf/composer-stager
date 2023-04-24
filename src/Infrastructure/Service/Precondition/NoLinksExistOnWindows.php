<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoLinksExistOnWindowsInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathListInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactoryInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinderInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Host\HostInterface;

/**
 * @package Precondition
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class NoLinksExistOnWindows extends AbstractFileIteratingPrecondition implements NoLinksExistOnWindowsInterface
{
    public function __construct(
        RecursiveFileFinderInterface $fileFinder,
        FilesystemInterface $filesystem,
        private readonly HostInterface $host,
        PathFactoryInterface $pathFactory,
    ) {
        parent::__construct($fileFinder, $filesystem, $pathFactory);
    }

    public function getName(): string
    {
        return 'No links exist on Windows';
    }

    public function getDescription(): string
    {
        return 'The codebase cannot contain links if on Windows.';
    }

    protected function exitEarly(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions,
    ): bool {
        // This is a Windows-specific precondition. No need to run it anywhere else.
        return !$this->host::isWindows();
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'There are no links in the codebase if on Windows.';
    }

    protected function getDefaultUnfulfilledStatusMessage(): string
    {
        return 'The %s directory at "%s" contains links, which is not supported on Windows. The first one is "%s".';
    }

    protected function isSupportedFile(PathInterface $file, PathInterface $codebaseRootDir): bool
    {
        // This code is host-specific, so it shouldn't be counted against code coverage
        // numbers. Nevertheless, it IS covered by tests on Windows-based CI jobs.
        return !$this->filesystem->isLink($file); // @codeCoverageIgnore
    }
}
