<?php

namespace PhpTuf\ComposerStager\Infrastructure\Value\Path;

final class UnixLikePath extends AbstractPath
{
    public function getAbsolute(): string
    {
        $path = $this->makeAbsolute($this->path);
        return $this->normalize($path);
    }

    private function makeAbsolute(string $path): string
    {
        // If the path is already absolute, return it as-is.
        if ($this->isAbsolute($path)) {
            return $path;
        }

        // Otherwise, prefix the CWD.
        return $this->cwd . DIRECTORY_SEPARATOR . $path;
    }

    private function isAbsolute(string $path): bool
    {
        return strpos($path, DIRECTORY_SEPARATOR) === 0;
    }
}
