<?php

namespace PhpTuf\ComposerStager\Infrastructure\Value\Path;

use PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

final class UnixLikePath implements PathInterface
{
    /**
     * @var \PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface
     */
    private $filesystem;

    /**
     * @var string
     */
    private $path;

    public function __construct(FilesystemInterface $filesystem, string $path)
    {
        $this->filesystem = $filesystem;
        $this->path = $path;
    }

    public function __toString(): string
    {
        return $this->getAbsolute();
    }

    public function getAbsolute(): string
    {
        $path = $this->path;

        // If the path isn't already absolute, prefix the CWD.
        if (!$this->isAbsolute($path)) {
            $cwd = $this->filesystem->getcwd();
            $path = $cwd . DIRECTORY_SEPARATOR . $path;
        }

        return $this->normalize($path);
    }

    private function isAbsolute(string $path): bool
    {
        return strpos($path, DIRECTORY_SEPARATOR) === 0;
    }

    private function normalize(string $absolutePath): string
    {
        // Explode around directory separators.
        $absolutePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $absolutePath);
        $parts = explode(DIRECTORY_SEPARATOR, $absolutePath);

        $normalized = [];
        foreach ($parts as $part) {
            // A zero-length part comes from (meaningless) double slashes. Skip it.
            if ($part === '') {
                continue;
            }

            // A single dot has no effect. Skip it.
            if ($part === '.') {
                continue;
            }

            // Two dots goes "up" a directory. Pop one off the current normalized array.
            if ($part === '..') {
                array_pop($normalized);
                continue;
            }

            // Otherwise, add the part to the current normalized array.
            $normalized[] = $part;
        }

        // Replace the directory separators, including the leading separator
        // that was removed by earlier array explosion, and return.
        return DIRECTORY_SEPARATOR . implode('/', $normalized);
    }
}
