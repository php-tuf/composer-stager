<?php

namespace PhpTuf\ComposerStager\Infrastructure\Value\Path;

use PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

abstract class AbstractPath implements PathInterface
{
    /**
     * @var \PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $path;

    public function __construct(FilesystemInterface $filesystem, string $path)
    {
        $this->filesystem = $filesystem;
        $this->path = $path;
    }

    final public function __toString(): string
    {
        return $this->getAbsolute();
    }

    abstract public function getAbsolute(): string;

    protected function normalize(string $absolutePath, string $prefix = ''): string
    {
        // If the absolute path begins with a directory separator, append it to
        // the prefix, or it will be lost below when exploding the string. (A
        // trailing directory separator SHOULD BE lost.)
        if (strpos($absolutePath, DIRECTORY_SEPARATOR) === 0) {
            $prefix .= DIRECTORY_SEPARATOR;
        }

        // Strip the given prefix.
        $absolutePath = substr($absolutePath, strlen($prefix));

        // Normalize directory separators and explode around them.
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

        // Replace directory separators.
        $normalized = implode(DIRECTORY_SEPARATOR, $normalized);

        // Replace the prefix and return.
        return $prefix . $normalized;
    }
}
