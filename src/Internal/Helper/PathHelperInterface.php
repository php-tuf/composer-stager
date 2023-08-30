<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Helper;

/**
 * @package Helper
 *
 * @internal Don't depend directly on this interface. It may be changed or removed at any time without notice.
 */
interface PathHelperInterface
{
    /** Canonicalizes the given path. */
    public static function canonicalize(string $path): string;

    /** Determines whether the given path is absolute. */
    public static function isAbsolute(string $path): bool;
}
