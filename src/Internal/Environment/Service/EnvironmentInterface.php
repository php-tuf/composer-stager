<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Environment\Service;

/**
 * Provides features for interacting with the PHP environment.
 *
 * @package Environment
 *
 * @internal Don't depend directly on this interface. It may be changed or removed at any time without notice.
 */
interface EnvironmentInterface
{
    /**
     * Limits the maximum execution time of the current script in seconds.
     *
     * This exists to prevent errors in the common case on shared hosting of
     * the built-in `set_time_limit()` function being disabled. In that case,
     * the request will be silently ignored and `false` will be returned. In
     * every other way, this behaves exactly like `set_time_limit()`.
     *
     * @param int $seconds
     *   The maximum execution time, in seconds. If set to zero (0),
     *   no time limit is imposed.
     *
     * @return bool
     *   Returns true on success, or false on failure--including if it didn't
     *   even try due to `set_time_limit()` being disabled.
     *
     * @see https://www.php.net/manual/en/function.set-time-limit.php
     */
    public function setTimeLimit(int $seconds): bool;
}
