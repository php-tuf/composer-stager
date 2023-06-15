<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Path\Factory;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Internal\Host\Service\Host;
use PhpTuf\ComposerStager\Internal\Path\Value\UnixLikePath;
use PhpTuf\ComposerStager\Internal\Path\Value\WindowsPath;

/**
 * @package Path
 *
 * @internal Don't depend on this class. It may be changed or removed at any time without notice.
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
