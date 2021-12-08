<?php

namespace PhpTuf\ComposerStager\Infrastructure\FileSyncer;

use FilesystemIterator;
use PhpTuf\ComposerStager\Domain\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Domain\Process\OutputCallbackInterface;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Util\PathUtil;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class PhpFileSyncer implements FileSyncerInterface
{
    /**
     * @var \PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface
     */
    private $filesystem;

    /**
     * @var string
     */
    private $source = '';

    /**
     * @var string
     */
    private $destination = '';

    /**
     * @var string[]
     */
    private $exclusions = [];

    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function sync(
        string $source,
        string $destination,
        array $exclusions = [],
        ?OutputCallbackInterface $callback = null,
        ?int $timeout = 120
    ): void {
        $this->source = $this->processSource($source);
        $this->destination = $this->processDestination($destination);
        $this->exclusions = $this->processExclusions($exclusions);
        set_time_limit((int) $timeout);

        $this->deleteExtraneousFilesFromDestination();
        $this->copySourceFilesToDestination();
    }

    /**
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     */
    private function processSource(string $source): string
    {
        if (!$this->filesystem->exists($source)) {
            throw new DirectoryNotFoundException($source, 'The source directory does not exist at "%s"');
        }

        // Ensure a trailing slash once, now at the beginning, so all future operations can depend on it.
        return PathUtil::ensureTrailingSlash($source);
    }

    /**
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     */
    private function processDestination(string $destination): string
    {
        // Create the destination directory if it doesn't already exist.
        $this->filesystem->mkdir($destination);

        // Ensure a trailing slash once, now at the beginning, so all future operations can depend on it.
        return PathUtil::ensureTrailingSlash($destination);
    }

    /**
     * @param string[] $exclusions
     *
     * @return string[]
     */
    private function processExclusions(array $exclusions): array
    {
        // Normalize paths 1) for duplicate removal below and 2) to support
        // exclusion later on of paths ending with directory separators.
        $exclusions = array_map([PathUtil::class, 'stripTrailingSlash'], $exclusions);
        return array_unique($exclusions);
    }

    /**
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     */
    private function deleteExtraneousFilesFromDestination(): void
    {
        // There's no reason to look for deletions if the destination is already empty.
        if ($this->destinationIsEmpty()) {
            return;
        }

        $destinationFiles = $this->find($this->destination);

        foreach ($destinationFiles as $destinationFilePathname) {
            $relativePathname = self::getRelativePath($this->destination, $destinationFilePathname);
            $sourceFilePathname = $this->source . $relativePathname;

            // Don't iterate over the destination directory if it is a descendant
            // of the source directory (i.e., if it is "underneath" or "inside"
            // it) or it will itself be deleted in the process.
            if (strpos($destinationFilePathname, $this->source) === 0) {
                continue;
            }

            // If it doesn't exist in the source, delete it from the destination.
            if (!$this->filesystem->exists($sourceFilePathname)) {
                $this->filesystem->remove($destinationFilePathname);
            }
        }
    }

    private function destinationIsEmpty(): bool
    {
        return scandir($this->destination) === ['.', '..'];
    }

    /**
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     */
    private function copySourceFilesToDestination(): void
    {
        $sourceFiles = $this->find($this->source);

        foreach ($sourceFiles as $sourceFilePathname) {
            $relativePathname = self::getRelativePath($this->source, $sourceFilePathname);
            $destinationFilePathname = $this->destination . $relativePathname;

            // Copy the file--even if it already exists and is identical in the
            // destination. Obviously, this has performance implications, but
            // for lots of small files (the primary use case), the cost of
            // checking differences first would surely outweigh any savings.
            $this->filesystem->copy($sourceFilePathname, $destinationFilePathname);
        }
    }

    /**
     * @return string[]
     *   A list of file pathnames, each beginning with the given directory. The
     *   iterator cannot simply be returned because its element order is uncertain,
     *   so the extraneous file deletion function would fail later when it sometimes
     *   tried to delete files after it had already deleted their ancestors.
     *
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     *
     * @todo This class is (unsurprisingly) the largest and most complex in the
     *   codebase, and this method with its helpers accounts for over a third of
     *   that by all measures. Extract it to its own class and improve its tests.
     */
    private function find(string $directory): array
    {
        $directoryIterator = $this->getRecursiveDirectoryIterator($directory);

        $exclusions = $this->exclusions;
        $filterIterator = new RecursiveCallbackFilterIterator($directoryIterator, static function (
            string $foundPathname
        ) use (
            $directory,
            $exclusions
        ): bool {
            $relativePathname = self::getRelativePath($directory, $foundPathname);
            return !in_array($relativePathname, $exclusions, true);
        });

        /** @var \Traversable<string> $iterator */
        $iterator = new RecursiveIteratorIterator($filterIterator);

        // The iterator must be converted to a flat list of pathnames rather
        // than returned whole because its element order is uncertain, so the
        // extraneous file deletion that happens later would fail when it sometimes
        // tried to delete files after their ancestors had already been deleted.
        return iterator_to_array($iterator);
    }

    private static function getRelativePath(string $ancestor, string $path): string
    {
        $ancestor = PathUtil::ensureTrailingSlash($ancestor);
        if (strpos($path, $ancestor) === 0) {
            $path = substr($path, strlen($ancestor));
        }
        return $path;
    }

    /**
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     *
     * @codeCoverageIgnore It's theoretically possible for RecursiveDirectoryIterator
     *   to throw an exception here (because the given directory has disappeared)
     *   but extremely unlikely, and it's infeasible to simulate in automated
     *   tests--at least without way more trouble than it's worth.
     */
    private function getRecursiveDirectoryIterator(string $directory): RecursiveDirectoryIterator
    {
        try {
            return new RecursiveDirectoryIterator(
                $directory,
                FilesystemIterator::CURRENT_AS_PATHNAME | FilesystemIterator::SKIP_DOTS
            );
        } catch (\UnexpectedValueException $e) {
            throw new ProcessFailedException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
