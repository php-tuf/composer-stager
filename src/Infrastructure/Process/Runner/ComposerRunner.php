<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process\Runner;

/**
 * @internal
 */
final class ComposerRunner extends AbstractRunner implements ComposerRunnerInterface
{
    protected function executableName(): string
    {
        return 'composer'; // @codeCoverageIgnore
    }
}
