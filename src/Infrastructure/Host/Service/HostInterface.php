<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Host\Service;

/**
 * Provides basic utilities for interacting with the host.
 *
 * @package Host
 *
 * @api
 */
interface HostInterface
{
    /** Determines whether the operating system is Windows. */
    public static function isWindows(): bool;
}
