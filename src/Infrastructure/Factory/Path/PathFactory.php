<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Factory\Path;

use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Host\Host;
use PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath;
use PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath;

/**
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
