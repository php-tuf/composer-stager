<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\ProcessRunner\Service;

use PhpTuf\ComposerStager\Domain\ProcessRunner\Service\ComposerRunnerInterface;

/**
 * @package ProcessRunner
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class ComposerRunner extends AbstractRunner implements ComposerRunnerInterface
{
    protected function executableName(): string
    {
        return 'composer'; // @codeCoverageIgnore
    }
}
