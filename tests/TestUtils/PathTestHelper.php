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
    private const TEST_ENV = 'var/phpunit/test-env';
    private const FRESH_FIXTURES_DIR = 'fresh-fixtures';
    private const PERSISTENT_FIXTURES_DIR = 'persistent-fixtures';
    private const ACTIVE_DIR = 'active-dir';
    private const STAGING_DIR = 'staging-dir';
    private const SOURCE_DIR = 'source-dir';
    private const DESTINATION_DIR = 'destination-dir';
    private const ARBITRARY_DIR = 'arbitrary-dir';
    private const ARBITRARY_FILE = 'arbitrary-file.txt';
    private const NON_EXISTENT_DIR = 'non-existent-dir';
    private const NON_EXISTENT_FILE = 'non-existent-file.txt';

    public static function repositoryRootAbsolute(): string
    {
        return dirname(__DIR__, 2);
    }

    public static function testEnvAbsolute(): string
    {
        return self::makeAbsolute(self::TEST_ENV, self::repositoryRootAbsolute());
    }

    public static function testFreshFixturesDirAbsolute(): string
    {
        return self::makeAbsolute(self::FRESH_FIXTURES_DIR, self::testEnvAbsolute());
    }

    public static function testPersistentFixturesAbsolute(): string
    {
        return self::makeAbsolute(self::PERSISTENT_FIXTURES_DIR, self::testEnvAbsolute());
    }

    public static function activeDirRelative(): string
    {
        return self::ACTIVE_DIR;
    }

    public static function activeDirAbsolute(): string
    {
        return self::makeAbsolute(self::activeDirRelative());
    }

    public static function activeDirPath(): PathInterface
    {
        return self::createPath(self::activeDirAbsolute());
    }

    public static function stagingDirRelative(): string
    {
        return self::STAGING_DIR;
    }

    public static function stagingDirAbsolute(): string
    {
        return self::makeAbsolute(self::stagingDirRelative());
    }

    public static function stagingDirPath(): PathInterface
    {
        return self::createPath(self::stagingDirAbsolute());
    }

    public static function sourceDirRelative(): string
    {
        return self::SOURCE_DIR;
    }

    public static function sourceDirAbsolute(): string
    {
        return self::makeAbsolute(self::sourceDirRelative());
    }

    public static function sourceDirPath(): PathInterface
    {
        return self::createPath(self::sourceDirAbsolute());
    }

    public static function destinationDirRelative(): string
    {
        return self::DESTINATION_DIR;
    }

    public static function destinationDirAbsolute(): string
    {
        return self::makeAbsolute(self::destinationDirRelative());
    }

    public static function destinationDirPath(): PathInterface
    {
        return self::createPath(self::destinationDirAbsolute());
    }

    public static function arbitraryDirRelative(): string
    {
        return self::ARBITRARY_DIR;
    }

    public static function arbitraryDirAbsolute(): string
    {
        return self::makeAbsolute(self::arbitraryDirRelative());
    }

    public static function arbitraryDirPath(): PathInterface
    {
        return self::createPath(self::arbitraryDirAbsolute());
    }

    public static function arbitraryFileRelative(): string
    {
        return self::ARBITRARY_FILE;
    }

    public static function arbitraryFileAbsolute(): string
    {
        return self::makeAbsolute(self::arbitraryFileRelative());
    }

    public static function arbitraryFilePath(): PathInterface
    {
        return self::createPath(self::arbitraryFileAbsolute());
    }

    public static function nonExistentDirRelative(): string
    {
        return self::NON_EXISTENT_DIR;
    }

    public static function nonExistentDirAbsolute(): string
    {
        return self::makeAbsolute(self::nonExistentDirRelative());
    }

    public static function nonExistentDirPath(): PathInterface
    {
        return self::createPath(self::nonExistentDirAbsolute());
    }

    public static function nonExistentFileRelative(): string
    {
        return self::NON_EXISTENT_FILE;
    }

    public static function nonExistentFileAbsolute(): string
    {
        return self::makeAbsolute(self::nonExistentFileRelative());
    }

    public static function nonExistentFilePath(): PathInterface
    {
        return self::createPath(self::nonExistentFileAbsolute());
    }

    public static function createPath(string $path, ?string $basePath = null): PathInterface
    {
        $basePath ??= self::testFreshFixturesDirAbsolute();
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
        $basePath ??= self::testFreshFixturesDirAbsolute();

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
