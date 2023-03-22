<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Value\Path;

/**
 * Handles a Unix-like filesystem path string.
 *
 * For all practical purposes, that means anything but Windows.
 *
 * Don't instantiate this class directory--use the path factory.
 *
 * @see \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory
 * @see https://en.wikipedia.org/wiki/Unix-like
 */
final class UnixLikePath extends AbstractPath
{
    public function isAbsolute(): bool
    {
        return str_starts_with($this->path, DIRECTORY_SEPARATOR);
    }

    protected function doResolve(string $basePath): string
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
