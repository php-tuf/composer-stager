<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Process\Service;

/**
 * @package Process
 *
 * @internal Don't depend on this class. It may be changed or removed at any time without notice.
 */
final class ComposerProcessProcessRunner extends AbstractProcessRunner implements ComposerProcessRunnerInterface
{
    protected function executableName(): string
    {
        return 'composer'; // @codeCoverageIgnore
    }
}
