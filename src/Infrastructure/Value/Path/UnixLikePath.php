<?php

namespace PhpTuf\ComposerStager\Infrastructure\Value\Path;

final class UnixLikePath extends AbstractPath
{
    protected function doResolve(string $basePath): string
    {
        $absolute = $this->makeAbsolute($basePath);
        return $this->normalize($absolute);
    }

    private function makeAbsolute(string $basePath): string
    {
        $path = $this->path;

        // If the path is already absolute, return it as-is.
        if ($this->isAbsolute($path)) {
            return $path;
        }

        // Otherwise, prefix the base path.
        return $basePath . DIRECTORY_SEPARATOR . $path;
    }

    private function isAbsolute(string $path): bool
    {
        return strpos($path, DIRECTORY_SEPARATOR) === 0;
    }
}
