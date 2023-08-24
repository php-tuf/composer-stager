<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Environment\Service;

/**
 * @package Environment
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class Environment implements EnvironmentInterface
{
    public function isWindows(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }

    public function setTimeLimit(int $seconds): bool
    {
        if (!function_exists('set_time_limit')) {
            // It's impractical to mock a built-in class for the sake of code coverage. Ignore.
            return false; // @codeCoverageIgnore
        }

        return set_time_limit($seconds);
    }
}
