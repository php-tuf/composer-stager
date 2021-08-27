<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process\FileCopier;

use LogicException;
use PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\IOException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Util\DirectoryUtil;
use Symfony\Component\Finder\Finder;

/**
 * @internal
 */
final class PhpFileCopier implements PhpFileCopierInterface
{
    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface
     */
    private $filesystem;

    /**
     * @var \Symfony\Component\Finder\Finder
     */
    private $fromFinder;

    /**
     * @var \Symfony\Component\Finder\Finder
     */
    private $toFinder;

    public function __construct(
        FilesystemInterface $filesystem,
        Finder $fromIterator,
        Finder $toIterator
    ) {
        $this->filesystem = $filesystem;

        // Injected Finders must always be cloned to avoid reusing and polluting
        // the same instance from being reused and thus getting polluted.
        $this->fromFinder = clone $fromIterator;
        $this->toFinder = clone $toIterator;
    }

    public function copy(
        string $from,
        string $to,
        ?array $exclusions = [],
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = 120
    ): void {
        $from = DirectoryUtil::stripTrailingSlash($from);
        $to = DirectoryUtil::stripTrailingSlash($to);

        set_time_limit((int) $timeout);

        try {
            $this->mirror($from, $to, (array) $exclusions);
        } catch (IOException | LogicException $e) {
            throw new ProcessFailedException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @param string[] $exclusions
     *
     * @todo This whole method (and the rest of the class) could possibly be replaced with
     *   a simple call to \Symfony\Component\Filesystem\Filesystem::mirror, but that
     *   method has a bug affecting our use case. Consider using it instead once it's
     *   fixed. Just be sure it has feature-parity with our path-handling--particularly,
     *   support for an empty string as a relative path. See the unit tests.
     * @see \Symfony\Component\Filesystem\Filesystem::mirror
     * @see https://github.com/symfony/symfony/issues/14068
     *
     * @throws \LogicException
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     */
    private function mirror(string $from, string $to, array $exclusions = []): void
    {
        $this->indexDirectories($from, $to, $exclusions);
        $this->deleteExtraneousFilesFromToDirectory($from, $to);
        $this->copyNewFilesToToDirectory($from, $to);
    }

    /**
     * @param string[] $exclusions
     *
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     */
    private function indexDirectories(string $from, string $to, array $exclusions): void
    {
        // Index the "from" directory.
        try {
            $this->fromFinder
                ->in($from)
                ->notPath($exclusions);
        } catch (\Symfony\Component\Finder\Exception\DirectoryNotFoundException $e) {
            throw new DirectoryNotFoundException(
                $from,
                'The "copy from" directory does not exist at "%s"',
                (int) $e->getCode(),
                $e
            );
        }

        // Index the "to" directory.
        try {
            // Ensure the "to" directory's presence. (This has no effect if it
            // already exists.)
            $this->filesystem->mkdir($to);
            // Index it.
            $this->toFinder
                ->in($to)
                ->notPath($exclusions);
        } catch (\Symfony\Component\Finder\Exception\DirectoryNotFoundException | IOException $e) {
            $message = sprintf('The "copy to" directory could not be created at "%s".', $to);
            throw new ProcessFailedException($message, (int) $e->getCode(), $e);
        }
    }

    /**
     * @throws \LogicException
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     */
    private function deleteExtraneousFilesFromToDirectory(string $from, string $to): void
    {
        $from = DirectoryUtil::ensureTrailingSlash($from);

        /** @var \Symfony\Component\Finder\SplFileInfo $toPath */
        foreach ($this->toFinder as $toPath) {
            $relativePathname = DirectoryUtil::getDescendantRelativeToAncestor($to, $toPath->getPathname());

            $fromPathname = $from . $relativePathname;

            if (!$this->filesystem->exists($fromPathname)) {
                $this->filesystem->remove($toPath->getPathname());
            }
        }
    }

    /**
     * @throws \LogicException
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function copyNewFilesToToDirectory(string $from, string $to): void
    {
        $to = DirectoryUtil::ensureTrailingSlash($to);

        /** @var \Symfony\Component\Finder\SplFileInfo $fromPath */
        foreach ($this->fromFinder as $fromPath) {
            $fromPathname = $fromPath->getPathname();

            $relativePathname = DirectoryUtil::getDescendantRelativeToAncestor($from, $fromPathname);
            $toPath = $to . $relativePathname;

            // Note: Symlinks will be treated as the paths they point to.
            if ($this->filesystem->isDir($fromPathname)) {
                $this->filesystem->mkdir($toPath);
            } elseif ($this->filesystem->isFile($fromPathname)) {
                $this->filesystem->copy($fromPathname, $toPath);
            } else {
                throw new IOException(
                    sprintf('Unable to determine file type of "%s".', $fromPathname)
                );
            }
        }
    }
}
