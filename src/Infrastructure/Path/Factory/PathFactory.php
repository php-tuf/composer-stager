<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Path\Factory;

use PhpTuf\ComposerStager\Domain\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Infrastructure\Path\Value\UnixLikePath;
use PhpTuf\ComposerStager\Infrastructure\Path\Value\WindowsPath;
use PhpTuf\ComposerStager\Infrastructure\Service\Host\Host;

/**
 * @package Path
 *
 * @api
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
final class PathFactory implements PathFactoryInterface
{
    public static function create(string $path, ?PathInterface $cwd = null): PathInterface
    {
        if (Host::isWindows()) {
            return new WindowsPath($path, $cwd); // @codeCoverageIgnore
        }

        return new UnixLikePath($path, $cwd);
    }
}
