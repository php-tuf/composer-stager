<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Host\Service;

/**
 * @package Host
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class Host implements HostInterface
{
    public static function isWindows(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }
}
