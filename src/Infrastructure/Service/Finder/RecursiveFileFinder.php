<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Finder;

use FilesystemIterator;
use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactoryInterface;
use PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use UnexpectedValueException;

final class RecursiveFileFinder implements RecursiveFileFinderInterface
{
    /** @var \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactoryInterface */
    private $pathFactory;

    public function __construct(PathFactoryInterface $pathFactory)
    {
        $this->pathFactory = $pathFactory;
    }

    public function find(PathInterface $directory, ?PathListInterface $exclusions = null): array
    {
        $exclusions = $exclusions ?? new PathList([]);

        $directoryIterator = $this->getRecursiveDirectoryIterator($directory->resolve());

        $exclusions = array_map(function ($path) use ($directory): string {
            $path = $this->pathFactory::create($path);

            return $path->resolveRelativeTo($directory);
        }, $exclusions->getAll());

        $filterIterator = new RecursiveCallbackFilterIterator($directoryIterator, static function (
            string $foundPathname
        ) use ($exclusions): bool {
            // On the surface, it may look like individual descendants of an excluded
            // directory, i.e., files "underneath" or "inside" it, won't be excluded
            // because they aren't individually in the array in order to be matched.
            // But because the directory iterator is recursive, their excluded
            // ancestor WILL BE found, and they will be excluded by extension.
            return !in_array($foundPathname, $exclusions, true);
        });

        /** @var \Traversable<string> $iterator */
        $iterator = new RecursiveIteratorIterator($filterIterator);

        // The iterator must be converted to a flat list of pathnames rather
        // than returned whole because its element order is uncertain, leading to
        // unpredictable behavior in some use cases. For example, a client that
        // uses the result for file deletion would fail when it sometimes tried
        // to delete files after their ancestors had already been deleted.
        $files = iterator_to_array($iterator);

        sort($files);

        return $files;
    }

    /**
     * @throws \PhpTuf\ComposerStager\Domain\Exception\IOException
     *   If the directory cannot be found or is not actually a directory.
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
                FilesystemIterator::CURRENT_AS_PATHNAME | FilesystemIterator::SKIP_DOTS,
            );
        } catch (UnexpectedValueException $e) {
            throw new IOException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
