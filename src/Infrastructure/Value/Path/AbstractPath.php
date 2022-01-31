<?php

namespace PhpTuf\ComposerStager\Infrastructure\Value\Path;

use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

abstract class AbstractPath implements PathInterface
{
    /**
     * @var string
     */
    protected $cwd;

    /**
     * @var string
     */
    protected $path;

    public function __construct(string $path)
    {
        $this->path = $path;

        // Especially since it accepts relative paths, an immutable path value
        // object should be immune to environmental details like the current
        // working directory. Cache the CWD at time of creation.
        $this->cwd = $this->getcwd();
    }

    public function resolve(): string
    {
        return $this->doResolve($this->cwd);
    }

    public function resolveRelativeTo(PathInterface $path): string
    {
        $basePath = $path->resolve();
        return $this->doResolve($basePath);
    }

    abstract protected function doResolve(string $basePath): string;

    /**
     * In order to avoid class dependencies, PHP's internal getcwd() function is
     * called directly here. For comparison...
     * @see \PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem::getcwd
     */
    private function getcwd(): string
    {
        return (string) getcwd();
    }

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
