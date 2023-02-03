<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\Host;

/** Provides basic utilities for interacting with the host. */
interface HostInterface
{
    /** Determines whether the operating system is Windows. */
    public function isWindows(): bool;
}
