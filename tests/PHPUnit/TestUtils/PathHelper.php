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

    public static function repositoryRootAbsolute(): string
    {
        return dirname(__DIR__, 3);
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
        return new TestPath(self::activeDirAbsolute());
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
        return new TestPath(self::stagingDirAbsolute());
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
