<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

use PhpTuf\ComposerStager\API\Path\Factory\PathListFactoryInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathListFactory;
use PhpTuf\ComposerStager\Internal\Path\Service\PathHelper;
use PhpTuf\ComposerStager\Internal\Path\Service\PathHelperInterface;
use Symfony\Component\Filesystem\Path as SymfonyPath;

final class PathTestHelper
{
    public static function createPath(string $path, ?string $basePath = null): PathInterface
    {
        $basePath ??= FixtureTestHelper::testFreshFixturesDirAbsolute();
        $basePath = self::createPathFactory()->create($basePath);

        return self::createPathFactory()->create($path, $basePath);
    }

    public static function createPathFactory(): PathFactory
    {
        return new PathFactory(self::createPathHelper());
    }

    public static function createPathHelper(): PathHelperInterface
    {
        return new PathHelper();
    }

    public static function createPathList(string ...$paths): PathListInterface
    {
        return self::createPathListFactory()->create(...$paths);
    }

    public static function createPathListFactory(): PathListFactoryInterface
    {
        return new PathListFactory(self::createPathHelper());
    }

    public static function canonicalize(string $path): string
    {
        $path = SymfonyPath::canonicalize($path);

        return (string) preg_replace('#[\\\/]+#', '/', $path);
    }

    public static function isAbsolute(string $path): bool
    {
        return SymfonyPath::isAbsolute($path);
    }

    public static function makeAbsolute(array|string $paths, ?string $basePath = null): string|array
    {
        $paths = BasicTestHelper::ensureIsArray($paths);
        $basePath ??= FixtureTestHelper::testFreshFixturesDirAbsolute();

        $paths = array_map(static function ($path) use ($basePath): string {
            $absolute = SymfonyPath::makeAbsolute($path, $basePath);

            return self::canonicalize($absolute);
        }, $paths);

        return count($paths) === 1
            ? reset($paths)
            : $paths;
    }

    /** @phpcs:disable SlevomatCodingStandard.PHP.DisallowReference.DisallowedPassingByReference */
    public static function fixSeparatorsMultiple(&...$paths): void
    {
        foreach ($paths as &$path) {
            $path = self::fixSeparators($path);
        }
    }

    public static function fixSeparators(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    /**
     * Ensures that the given path ends with a slash (directory separator).
     *
     * @param string $path
     *   Any path, absolute or relative, existing or not.
     */
    public static function ensureTrailingSlash(string $path): string
    {
        if ($path === '') {
            $path = '.';
        }

        return self::stripTrailingSlash($path) . '/';
    }

    /**
     * Strips the trailing slash (directory separator) from a given path.
     *
     * @param string $path
     *   Any path, absolute or relative, existing or not. Empty paths and device
     *   roots will be returned unchanged. Remote paths and UNC (Universal
     *   Naming Convention) paths are not supported. No validation is done to
     *   ensure that given paths are valid.
     */
    public static function stripTrailingSlash(string $path): string
    {
        // Don't change a Windows drive letter root path, e.g., "C:\".
        if (preg_match('/^[a-z]:\\\\?$/i', $path) === 1) {
            return $path;
        }

        $trimmedPath = rtrim($path, '/\\');

        // Don't change a UNIX-like root path.
        if ($trimmedPath === '') {
            return $path;
        }

        return $trimmedPath;
    }
}
