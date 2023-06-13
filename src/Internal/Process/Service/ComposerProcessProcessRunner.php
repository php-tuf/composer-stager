<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Process\Service;

use PhpTuf\ComposerStager\API\Process\Service\ComposerProcessRunnerInterface;

/**
 * @package Process
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class ComposerProcessProcessRunner extends AbstractProcessRunner implements ComposerProcessRunnerInterface
{
    protected function executableName(): string
    {
        return 'composer'; // @codeCoverageIgnore
    }
}
