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
        return SymfonyPath::makeAbsolute(self::TEST_ENV, self::repositoryRootAbsolute());
    }

    public static function testWorkingDirAbsolute(): string
    {
        return SymfonyPath::makeAbsolute(self::WORKING_DIR, self::testEnvAbsolute());
    }

    public static function activeDirRelative(): string
    {
        return self::ACTIVE_DIR;
    }

    public static function activeDirAbsolute(): string
    {
        return SymfonyPath::makeAbsolute(self::activeDirRelative(), self::testWorkingDirAbsolute());
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
        return SymfonyPath::makeAbsolute(self::stagingDirRelative(), self::testWorkingDirAbsolute());
    }

    public static function stagingDirPath(): PathInterface
    {
        return new TestPath(self::stagingDirAbsolute());
    }
}
