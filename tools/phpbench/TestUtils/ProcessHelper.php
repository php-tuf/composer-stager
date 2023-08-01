<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPBench\TestUtils;

final class ProcessHelper
{
    // Use a long process timeout so that benchmarks can still be completed and
    // reported even if processes take an unreasonably long time to complete.
    public const PROCESS_TIMEOUT = 600;
}
