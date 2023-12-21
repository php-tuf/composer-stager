<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPath;
use Symfony\Component\Filesystem\Path as SymfonyPath;

final class PathHelper
{
    private const TEST_ENV = 'var/phpunit/test-env';
    private const FRESH_FIXTURES_DIR = 'fresh-fixtures';
    private const PERSISTENT_FIXTURES_DIR = 'persistent-fixtures';
    private const ACTIVE_DIR = 'active-dir';
    private const STAGING_DIR = 'staging-dir';
    private const SOURCE_DIR = 'source-dir';
    private const DESTINATION_DIR = 'destination-dir';

    public static function repositoryRootAbsolute(): string
    {
        return dirname(__DIR__, 2);
    }

    private static function testEnvAbsolute(): string
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
        return self::makeAbsolute(self::activeDirRelative(), self::testFreshFixturesDirAbsolute());
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
        return self::makeAbsolute(self::stagingDirRelative(), self::testFreshFixturesDirAbsolute());
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
        return self::makeAbsolute(self::sourceDirRelative(), self::testFreshFixturesDirAbsolute());
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
        return self::makeAbsolute(self::destinationDirRelative(), self::testFreshFixturesDirAbsolute());
    }

    public static function destinationDirPath(): PathInterface
    {
        return self::createPath(self::destinationDirAbsolute());
    }

    public static function nonExistentFileBasename(): string
    {
        return 'non-existent-file.txt';
    }

    public static function nonExistentFileAbsolute(): string
    {
        return self::makeAbsolute(self::nonExistentFileBasename(), '/var/www');
    }

    public static function nonExistentFilePath(): PathInterface
    {
        return self::createPath(self::nonExistentFileAbsolute());
    }

    public static function createPath(string $path, ?string $basePath = null): PathInterface
    {
        if (is_string($basePath)) {
            assert(self::isAbsolute($basePath));
            $path = self::makeAbsolute($path, $basePath);
        }

        return new TestPath($path);
    }

    public static function canonicalize(string $path): string
    {
        $path = SymfonyPath::canonicalize($path);

        return (string) preg_replace('#/+#', DIRECTORY_SEPARATOR, $path);
    }

    public static function isAbsolute(string $path): bool
    {
        return SymfonyPath::isAbsolute($path);
    }

    public static function makeAbsolute(string $path, string $basePath): string
    {
        $absolute = SymfonyPath::makeAbsolute($path, $basePath);

        return self::canonicalize($absolute);
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
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
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

        return self::stripTrailingSlash($path) . DIRECTORY_SEPARATOR;
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
