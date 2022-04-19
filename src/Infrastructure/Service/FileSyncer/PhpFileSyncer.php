<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer;

use FilesystemIterator;
use PhpTuf\ComposerStager\Domain\Exception\RuntimeException;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ProcessRunnerInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use UnexpectedValueException;

final class PhpFileSyncer implements PhpFileSyncerInterface
{
    /** @var \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface */
    private $filesystem;

    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /** @todo Do something with $callback. */
    public function sync(
        PathInterface $source,
        PathInterface $destination,
        ?PathListInterface $exclusions = null,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT
    ): void {
        set_time_limit((int) $timeout);

        $exclusions = $exclusions ?? new PathList([]);

        $this->assertSourceExists($source);
        $this->ensureDestinationExists($destination);
        $this->deleteExtraneousFilesFromDestination($destination, $source, $exclusions);
        $this->copySourceFilesToDestination($source, $destination, $exclusions);
    }

    /** @throws \PhpTuf\ComposerStager\Domain\Exception\RuntimeException */
    private function assertSourceExists(PathInterface $source): void
    {
        $source = $source->resolve();

        if (!$this->filesystem->exists($source)) {
            throw new RuntimeException(sprintf('The source directory does not exist at "%s"', $source));
        }
    }

    /** @throws \PhpTuf\ComposerStager\Domain\Exception\IOException */
    private function ensureDestinationExists(PathInterface $destination): void
    {
        // Create the destination directory if it doesn't already exist.
        $this->filesystem->mkdir($destination->resolve());
    }

    /**
     * @throws \PhpTuf\ComposerStager\Domain\Exception\IOException
     * @throws \PhpTuf\ComposerStager\Domain\Exception\RuntimeException
     */
    private function deleteExtraneousFilesFromDestination(
        PathInterface $destination,
        PathInterface $source,
        PathListInterface $exclusions
    ): void {
        // There's no reason to look for deletions if the destination is already empty.
        if ($this->destinationIsEmpty($destination)) {
            return;
        }

        $destinationFiles = $this->find($destination, $exclusions);

        $sourceResolved = $source->resolve();
        $destinationResolved = $destination->resolve();

        foreach ($destinationFiles as $destinationFilePathname) {
            $relativePathname = self::getRelativePath($destinationResolved, $destinationFilePathname);
            $sourceFilePathname = $sourceResolved . DIRECTORY_SEPARATOR . $relativePathname;

            // Don't iterate over the destination directory if it is a descendant
            // of the source directory (i.e., if it is "underneath" or "inside"
            // it) or it will itself be deleted in the process.
            if (strpos($destinationFilePathname, $sourceResolved) === 0) {
                continue;
            }

            if ($this->filesystem->exists($sourceFilePathname)) {
                continue;
            }

            // If it doesn't exist in the source, delete it from the destination.
            $this->filesystem->remove($destinationFilePathname);
        }
    }

    private function destinationIsEmpty(PathInterface $destination): bool
    {
        return scandir($destination->resolve()) === ['.', '..'];
    }

    /**
     * @throws \PhpTuf\ComposerStager\Domain\Exception\IOException
     * @throws \PhpTuf\ComposerStager\Domain\Exception\RuntimeException
     */
    private function copySourceFilesToDestination(
        PathInterface $source,
        PathInterface $destination,
        PathListInterface $exclusions
    ): void {
        $sourceFiles = $this->find($source, $exclusions);

        $sourceResolved = $source->resolve();
        $destinationResolved = $destination->resolve();

        foreach ($sourceFiles as $sourceFilePathname) {
            $relativePathname = self::getRelativePath($sourceResolved, $sourceFilePathname);
            $destinationFilePathname = $destinationResolved . DIRECTORY_SEPARATOR . $relativePathname;

            // Copy the file--even if it already exists and is identical in the
            // destination. Obviously, this has performance implications, but
            // for lots of small files (the primary use case), the cost of
            // checking differences first would surely outweigh any savings.
            $this->filesystem->copy($sourceFilePathname, $destinationFilePathname);
        }
    }

    /**
     * @return array<string>
     *   A list of file pathnames, each beginning with the given directory. The
     *   iterator cannot simply be returned because its element order is uncertain,
     *   so the extraneous file deletion function would fail later when it sometimes
     *   tried to delete files after it had already deleted their ancestors.
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\RuntimeException
     *
     * @todo This class is (unsurprisingly) the largest and most complex in the
     *   codebase, and this method with its helpers accounts for over a third of
     *   that by all measures. Extract it to its own class and improve its tests.
     */
    private function find(PathInterface $directory, PathListInterface $exclusions): array
    {
        $directoryIterator = $this->getRecursiveDirectoryIterator($directory->resolve());

        $exclusions = array_map(static function ($path) use ($directory): string {
            // @todo It's not good to depend on a static factory here. Find a
            //   different approach or at least make it overridable somehow.
            $path = PathFactory::create($path);
            return $path->resolveRelativeTo($directory);
        }, $exclusions->getAll());

        $filterIterator = new RecursiveCallbackFilterIterator($directoryIterator, static function (
            string $foundPathname
        ) use ($exclusions): bool {
            // On the surface, it may look like individual descendants of an excluded
            // directory (i.e., files "underneath" or "inside" it) won't be excluded
            // because they aren't individually in the array in order to be matched.
            // But because the directory iterator is recursive, their excluded
            // ancestor WILL BE found, and they will be excluded by extension.
            return !in_array($foundPathname, $exclusions, true);
        });

        /** @var \Traversable<string> $iterator */
        $iterator = new RecursiveIteratorIterator($filterIterator);

        // The iterator must be converted to a flat list of pathnames rather
        // than returned whole because its element order is uncertain, so the
        // extraneous file deletion that happens later would fail when it sometimes
        // tried to delete files after their ancestors had already been deleted.
        return iterator_to_array($iterator);
    }

    /**
     * @throws \PhpTuf\ComposerStager\Domain\Exception\RuntimeException
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
        } catch (UnexpectedValueException $e) {
            throw new RuntimeException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    private static function getRelativePath(string $ancestor, string $path): string
    {
        $ancestor .= DIRECTORY_SEPARATOR;

        if (strpos($path, $ancestor) === 0) {
            $path = substr($path, strlen($ancestor));
        }

        return $path;
    }
}
