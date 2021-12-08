<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process\Runner;

use PhpTuf\ComposerStager\Domain\Process\Runner\ComposerRunnerInterface;

final class ComposerRunner extends AbstractRunner implements ComposerRunnerInterface
{
    protected function executableName(): string
    {
        return 'composer'; // @codeCoverageIgnore
    }
}
