<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Host\Service;

/**
 * Provides basic utilities for interacting with the host.
 *
 * @package Host
 *
 * @internal Don't depend on this interface. It may be changed or removed at any time without notice.
 */
interface HostInterface
{
    /** Determines whether the operating system is Windows. */
    public static function isWindows(): bool;
}
