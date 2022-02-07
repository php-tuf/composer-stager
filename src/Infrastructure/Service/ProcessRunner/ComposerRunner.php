<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\ProcessRunner;

use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ComposerRunnerInterface;

final class ComposerRunner extends AbstractRunner implements ComposerRunnerInterface
{
    protected function executableName(): string
    {
        return 'composer'; // @codeCoverageIgnore
    }
}
