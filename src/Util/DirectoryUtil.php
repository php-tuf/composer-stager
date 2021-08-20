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

    public static function stripTrailingSeparator(string $path): string
    {
        return rtrim($path, '/\\');
    }

    public static function ensureTrailingSeparator(string $path): string
    {
        return self::stripTrailingSeparator($path) . DIRECTORY_SEPARATOR;
    }
}
