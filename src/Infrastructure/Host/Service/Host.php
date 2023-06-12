<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Host\Service;

/**
 * @package Host
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class Host implements HostInterface
{
    public static function isWindows(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }
}
