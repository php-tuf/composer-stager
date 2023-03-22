<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Value\Path;

/**
 * Handles a filesystem path string.
 *
 * The path string may be absolute or relative to the current working directory
 * as returned by `getcwd()` at runtime, e.g., "/var/www/example" or "example".
 * Nothing needs to actually exist at the path.
 *
 * @api
 */
interface PathInterface
{
    /** Determines whether the original path string as given is absolute, without resolving it. */
    public function isAbsolute(): bool;

    /** Gets the unresolved path string, exactly as given. */
    public function raw(): string;

    /**
     * Gets the fully resolved, absolute path string without trailing slash.
     *
     * Resolves this path relative to the current working directory as returned
     * by `getcwd()` at runtime, e.g., "/var/www/example" given a path of
     * "example" and a working directory of "/var/www".
     */
    public function resolved(): string;

    /**
     * Gets the fully resolved, absolute path string without trailing slash, relative to another given path.
     *
     * Resolves this path relative to the fully resolved path of the given path
     * object, e.g., "/usr/local/example" given a path of "example" and a Path
     * argument that resolves to "/usr/local". If this path is not a descendant
     * of the given path, this path will simply be resolved and returned, e.g.,
     * "/var/one/two/three" relative to "/var/four/five/six" would return
     * "/var/one/two/three".
     */
    public function resolvedRelativeTo(PathInterface $path): string;
}
