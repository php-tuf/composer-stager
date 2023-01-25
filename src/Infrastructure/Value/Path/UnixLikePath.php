<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Value\Path;

final class UnixLikePath extends AbstractPath
{
    public function isAbsolute(): bool
    {
        return strpos($this->path, DIRECTORY_SEPARATOR) === 0;
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
