<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process;

/**
 * @internal
 */
class ComposerRunner extends AbstractRunner
{
    protected function executableName(): string
    {
        return 'composer'; // @codeCoverageIgnore
    }
}
