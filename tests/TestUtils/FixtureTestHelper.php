<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

final class FixtureTestHelper
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
        return PathTestHelper::makeAbsolute(self::TEST_ENV, self::repositoryRootAbsolute());
    }

    public static function testFreshFixturesDirAbsolute(): string
    {
        return PathTestHelper::makeAbsolute(self::FRESH_FIXTURES_DIR, self::testEnvAbsolute());
    }

    public static function testPersistentFixturesAbsolute(): string
    {
        return PathTestHelper::makeAbsolute(self::PERSISTENT_FIXTURES_DIR, self::testEnvAbsolute());
    }

    public static function activeDirRelative(): string
    {
        return self::ACTIVE_DIR;
    }

    public static function activeDirAbsolute(): string
    {
        return PathTestHelper::makeAbsolute(self::activeDirRelative());
    }

    public static function activeDirPath(): PathInterface
    {
        return PathTestHelper::createPath(self::activeDirAbsolute());
    }

    public static function stagingDirRelative(): string
    {
        return self::STAGING_DIR;
    }

    public static function stagingDirAbsolute(): string
    {
        return PathTestHelper::makeAbsolute(self::stagingDirRelative());
    }

    public static function stagingDirPath(): PathInterface
    {
        return PathTestHelper::createPath(self::stagingDirAbsolute());
    }

    public static function sourceDirRelative(): string
    {
        return self::SOURCE_DIR;
    }

    public static function sourceDirAbsolute(): string
    {
        return PathTestHelper::makeAbsolute(self::sourceDirRelative());
    }

    public static function sourceDirPath(): PathInterface
    {
        return PathTestHelper::createPath(self::sourceDirAbsolute());
    }

    public static function destinationDirRelative(): string
    {
        return self::DESTINATION_DIR;
    }

    public static function destinationDirAbsolute(): string
    {
        return PathTestHelper::makeAbsolute(self::destinationDirRelative());
    }

    public static function destinationDirPath(): PathInterface
    {
        return PathTestHelper::createPath(self::destinationDirAbsolute());
    }

    public static function arbitraryDirRelative(): string
    {
        return self::ARBITRARY_DIR;
    }

    public static function arbitraryDirAbsolute(): string
    {
        return PathTestHelper::makeAbsolute(self::arbitraryDirRelative());
    }

    public static function arbitraryDirPath(): PathInterface
    {
        return PathTestHelper::createPath(self::arbitraryDirAbsolute());
    }

    public static function arbitraryFileRelative(): string
    {
        return self::ARBITRARY_FILE;
    }

    public static function arbitraryFileAbsolute(): string
    {
        return PathTestHelper::makeAbsolute(self::arbitraryFileRelative());
    }

    public static function arbitraryFilePath(): PathInterface
    {
        return PathTestHelper::createPath(self::arbitraryFileAbsolute());
    }

    public static function nonExistentDirRelative(): string
    {
        return self::NON_EXISTENT_DIR;
    }

    public static function nonExistentDirAbsolute(): string
    {
        return PathTestHelper::makeAbsolute(self::nonExistentDirRelative());
    }

    public static function nonExistentDirPath(): PathInterface
    {
        return PathTestHelper::createPath(self::nonExistentDirAbsolute());
    }

    public static function nonExistentFileRelative(): string
    {
        return self::NON_EXISTENT_FILE;
    }

    public static function nonExistentFileAbsolute(): string
    {
        return PathTestHelper::makeAbsolute(self::nonExistentFileRelative());
    }

    public static function nonExistentFilePath(): PathInterface
    {
        return PathTestHelper::createPath(self::nonExistentFileAbsolute());
    }

    public static function createTestEnvironment(?string $activeDir = null): void
    {
        $activeDir ??= self::activeDirRelative();

        self::removeTestEnvironment();

        // Create the active directory only. The staging directory is created
        // when the "begin" command is exercised.
        $workingDirAbsolute = self::testFreshFixturesDirAbsolute();
        $activeDirAbsolute = PathTestHelper::makeAbsolute($activeDir, $workingDirAbsolute);
        FilesystemTestHelper::mkdir([$workingDirAbsolute, $activeDirAbsolute]);
        chdir($workingDirAbsolute);
    }

    public static function removeTestEnvironment(): void
    {
        $filesystem = new Filesystem();

        if (!$filesystem->exists(self::testFreshFixturesDirAbsolute())) {
            return;
        }

        try {
            $filesystem->remove(self::testFreshFixturesDirAbsolute());
        } catch (IOException) {
            // @todo Windows chokes on this every time, e.g.,
            //    | Failed to remove directory
            //    | "D:\a\composer-stager\composer-stager\tests\Functional/../../var/phpunit/test-env-container":
            //    | rmdir(D:\a\composer-stager\composer-stager\tests\Functional/../../var/phpunit/test-env-container):
            //    | Resource temporarily unavailable.
            //   Obviously, this error suppression is likely to bite us in the future
            //   even though it doesn't seem to cause any problems now. Fix it.
            // @ignoreException
        }
    }
}
