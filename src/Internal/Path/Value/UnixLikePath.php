<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Path\Value;

/**
 * Handles a Unix-like filesystem path string.
 *
 * For all practical purposes, that means anything but Windows.
 *
 * @see \PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory
 * @see https://en.wikipedia.org/wiki/Unix-like
 *
 * @package Path
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class UnixLikePath extends AbstractPath
{
    public function isAbsolute(): bool
    {
        return str_starts_with($this->path, DIRECTORY_SEPARATOR);
    }

    protected function doAbsolute(string $basePath): string
    {
        $absolute = $this->makeAbsolute($basePath);

        return $this->normalize($absolute);
    }

    private function makeAbsolute(string $basePath): string
    {
        // If the path is already absolute, return it as-is.
        if ($this->isAbsolute()) {
            return $this->path;
        }

        // Otherwise, prefix the base path.
        return $basePath . DIRECTORY_SEPARATOR . $this->path;
    }
}
