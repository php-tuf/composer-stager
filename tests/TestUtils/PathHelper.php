<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPath;
use Symfony\Component\Filesystem\Path as SymfonyPath;

final class PathHelper
{
    private const TEST_ENV = 'var/phpunit/test-env';
    private const WORKING_DIR = 'working-dir';
    private const ACTIVE_DIR = 'active-dir';
    private const STAGING_DIR = 'staging-dir';
    private const SOURCE_DIR = 'source';
    private const DESTINATION_DIR = 'destination';

    public static function repositoryRootAbsolute(): string
    {
        return dirname(__DIR__, 2);
    }

    public static function testEnvAbsolute(): string
    {
        return self::makeAbsolute(self::TEST_ENV, self::repositoryRootAbsolute());
    }

    public static function testWorkingDirAbsolute(): string
    {
        return self::makeAbsolute(self::WORKING_DIR, self::testEnvAbsolute());
    }

    public static function activeDirRelative(): string
    {
        return self::ACTIVE_DIR;
    }

    public static function activeDirAbsolute(): string
    {
        return self::makeAbsolute(self::activeDirRelative(), self::testWorkingDirAbsolute());
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
        return self::makeAbsolute(self::stagingDirRelative(), self::testWorkingDirAbsolute());
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
        return self::makeAbsolute(self::sourceDirRelative(), self::testEnvAbsolute());
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
        return self::makeAbsolute(self::destinationDirRelative(), self::testEnvAbsolute());
    }

    public static function destinationDirPath(): PathInterface
    {
        return self::createPath(self::destinationDirAbsolute());
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
}
