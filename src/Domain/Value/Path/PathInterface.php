<?php

namespace PhpTuf\ComposerStager\Domain\Value\Path;

/**
 * Handles a filesystem path string.
 *
 * The path string may be absolute or relative to the current working directory (CWD),
 * e.g., "/var/www/example" or "example". Nothing needs to actually exist at the path.
 */
interface PathInterface
{
    /**
     * Gets the fully resolved, absolute path string without trailing slash.
     *
     * Resolves the path relative to the current working directory (CWD) at time
     * of creation, e.g., "/var/www/example" given a path of "example" and a
     * working directory of "/var/www".
     */
    public function resolve(): string;

    /**
     * Gets the fully resolved, absolute path string without trailing slash relative to another given path.
     *
     * Resolves the path relative to the fully resolved path of the given path
     * object, e.g., "/usr/local/example" given a path of "example" and a Path
     * argument that resolves to "/usr/local".
     */
    public function resolveRelativeTo(PathInterface $path): string;
}
