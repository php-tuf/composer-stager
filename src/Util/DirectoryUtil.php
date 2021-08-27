<?php

namespace PhpTuf\ComposerStager\Util;

use PhpTuf\ComposerStager\Exception\LogicException;

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
     * Strips the trailing slash (directory separator) from a given path.
     *
     * @param string $path
     *   Any path, absolute or relative, existing or not.
     *
     * @return string
     */
    public static function stripTrailingSlash(string $path): string
    {
        return rtrim($path, '/\\');
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
        return self::stripTrailingSlash($path) . DIRECTORY_SEPARATOR;
    }

    /**
     * Gets the path of a given path relative to a given base.
     *
     * @param string $ancestor
     *   A path, absolute or relative, existing or not.
     * @param string $descendant
     *   A path, relative to the given ancestor, existing or not.
     *
     * @throws \PhpTuf\ComposerStager\Exception\LogicException
     */
    public static function getDescendantRelativeToAncestor(string $ancestor, string $descendant): string
    {
        if (self::stripTrailingSlash($ancestor) === self::stripTrailingSlash($descendant)) {
            throw new LogicException('Ancestor cannot be equal to descendant.');
        }

        $ancestorDirLen = strlen(self::ensureTrailingSlash($ancestor));
        if ($ancestor === '') {
            $ancestorDirLen = 0;
        }

        $relativePath = substr($descendant, $ancestorDirLen);

        if ((bool) $relativePath === false) {
            throw new LogicException('Descendant is outside ancestor.');
        }

        return self::stripTrailingSlash($relativePath);
    }
}
