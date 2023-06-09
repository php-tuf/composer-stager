<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Finder;

use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathListInterface;

/**
 * Recursively finds all files "underneath" or "inside" a directory.
 *
 * @package Finder
 *
 * @api
 */
interface FileFinderInterface
{
    /**
     * Recursively finds all files "underneath" or "inside" a given directory.
     *
     * Returns files only--no directories.
     *
     * @param \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $directory
     *   The directory to search.
     * @param \PhpTuf\ComposerStager\Domain\Value\Path\PathListInterface|null $exclusions
     *   Paths to exclude, relative to the active directory.
     *
     * @return array<string>
     *   A sorted list of absolute file pathnames, each beginning with the
     *   given directory. For example, given "/var/www" as a directory:
     *
     *   - /var/www/four/five/six.txt
     *   - /var/www/one.txt
     *   - /var/www/two/three.txt
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\IOException
     *   If the directory cannot be found or is not actually a directory.
     */
    public function find(PathInterface $directory, ?PathListInterface $exclusions = null): array;
}
