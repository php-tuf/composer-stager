<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Finder;

use FilesystemIterator;
use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableAwareTrait;
use PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathListInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactoryInterface;
use PhpTuf\ComposerStager\Infrastructure\Value\Path\PathList;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use UnexpectedValueException;

/**
 * @package Finder
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class FileFinder implements FileFinderInterface
{
    use TranslatableAwareTrait;

    public function __construct(
        private readonly PathFactoryInterface $pathFactory,
        TranslatableFactoryInterface $translatableFactory,
    ) {
        $this->setTranslatableFactory($translatableFactory);
    }

    public function find(PathInterface $directory, ?PathListInterface $exclusions = null): array
    {
        $exclusions ??= new PathList();

        $directoryIterator = $this->getRecursiveDirectoryIterator($directory->resolved());

        // Resolve the exclusions relative to the search directory.
        $exclusions = array_map(fn ($path): string => $this->pathFactory::create($path)
            ->resolvedRelativeTo($directory), $exclusions->getAll());

        // Apply exclusions. On the surface, it may look like individual descendants
        // of an excluded directory, i.e., files "underneath" or "inside" it, won't
        // be excluded because they aren't individually in the array in order to be
        // matched. But because the directory iterator is recursive, their excluded
        // ancestor WILL BE found, and they will be excluded by extension.
        $filterIterator = new RecursiveCallbackFilterIterator($directoryIterator, static fn (
            string $foundPathname,
        ): bool => !in_array($foundPathname, $exclusions, true));

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
            throw new IOException($this->t($e->getMessage()), 0, $e);
        }
    }
}
