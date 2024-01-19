<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\API\Finder\Service;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use RecursiveIteratorIterator;

/**
 * Recursively finds all files "underneath" or "inside" a directory.
 *
 * @package Finder
 *
 * @api This interface is subject to our backward compatibility promise and may be safely depended upon.
 */
interface FileFinderInterface
{
    /**
     * Recursively finds all files and directories "underneath" or "inside" a given directory.
     *
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $directory
     *   The directory to search.
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathListInterface|null $exclusions
     *   Paths to exclude, relative to the active directory, e.g.:
     *   ```php
     *   $exclusions = $this->pathListFactory->create(
     *       'cache',
     *       'uploads',
     *   );
     *   ```
     *
     * @return \RecursiveIteratorIterator
     *   Returns a filtered (see `$exclusions`) recursive directory iterator.
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\IOException
     *   If the directory cannot be found or is not actually a directory.
     */
    public function find(PathInterface $directory, ?PathListInterface $exclusions = null): RecursiveIteratorIterator;
}
