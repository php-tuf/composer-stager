<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\Host\HostInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoLinksExistOnWindowsInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactoryInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinderInterface;

final class NoLinksExistOnWindows extends AbstractLinkIteratingPrecondition implements NoLinksExistOnWindowsInterface
{
    private HostInterface $host;

    public function __construct(
        RecursiveFileFinderInterface $fileFinder,
        FilesystemInterface $filesystem,
        HostInterface $host,
        PathFactoryInterface $pathFactory
    ) {
        parent::__construct($fileFinder, $filesystem, $pathFactory);

        $this->host = $host;
    }

    public function getName(): string
    {
        return 'No links exist on Windows'; // @codeCoverageIgnore
    }

    public function getDescription(): string
    {
        return 'The codebase cannot contain links if on Windows.'; // @codeCoverageIgnore
    }

    protected function exitEarly(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions
    ): bool {
        // This is a Windows-specific precondition. No need to run it anywhere else.
        return !$this->host->isWindows();
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'There are no links in the codebase if on Windows.';
    }

    protected function getDefaultUnfulfilledStatusMessage(): string
    {
        return 'The %s directory at "%s" contains links, which is not supported on Windows. The first one is "%s".';
    }

    protected function isSupportedLink(PathInterface $file, PathInterface $codebaseRootDir): bool
    {
        // This code is host-specific, so it shouldn't be counted against code coverage
        // numbers. Nevertheless, it IS covered by tests on Windows-based CI jobs.
        return !$this->filesystem->isLink($file); // @codeCoverageIgnore
    }
}
