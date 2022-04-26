<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\ProcessRunner;

/** Runs shell processes. */
interface ProcessRunnerInterface
{
    /** The default process timeout. */
    public const DEFAULT_TIMEOUT = 120;
}
