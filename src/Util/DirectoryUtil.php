<?php

namespace PhpTuf\ComposerStager\Util;

/**
 * @internal
 */
final class DirectoryUtil
{
    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * Makes a path relative to a given ancestor by stripping said ancestor from
     * the front of it.
     *
     * If the path is not a descendant of the ancestor (i.e., it does not begin
     * with the ancestor), it will be returned unchanged, with no error.
     *
     * @param string $path
     *   The path to strip the ancestor from, e.g., "/var/www/ancestor/descendant".
     * @param string $ancestor
     *   The ancestor to strip from the path, e.g., "/var/www/ancestor".
     *
     * @return string
     *   The resulting path, relative to the ancestor, e.g., "descendant".
     */
    public static function getPathRelativeToAncestor(string $path, string $ancestor): string
    {
        $ancestor = self::ensureTrailingSlash($ancestor);
        if (strpos($path, $ancestor) === 0) {
            $path = substr($path, strlen($ancestor));
        }
        return $path;
    }

    /**
     * Strips the trailing slash (directory separator) from a given path.
     *
     * @param string $path
     *   Any path, absolute or relative, existing or not. Empty paths and device
     *   roots will be returned unchanged. Remote paths and UNC (Universal
     *   Naming Convention) paths are not supported. No validation is done to
     *   ensure that given paths are valid.
     */
    public static function stripTrailingSlash(string $path): string
    {
        // Don't change a Windows drive letter root path, e.g., "C:\".
        if (preg_match('/^[a-z]:\\\\?$/i', $path) === 1) {
            return $path;
        }

        $trimmedPath = rtrim($path, '/\\');

        // Don't change a UNIX-like root path.
        if ($trimmedPath === '') {
            return $path;
        }

        return $trimmedPath;
    }

    /**
     * Ensures that the given path ends with a slash (directory separator).
     *
     * @param string $path
     *   Any path, absolute or relative, existing or not.
     *
     * @return string
     */
    public static function ensureTrailingSlash(string $path): string
    {
        if ($path === '') {
            $path = '.';
        }

        return self::stripTrailingSlash($path) . DIRECTORY_SEPARATOR;
    }
}
