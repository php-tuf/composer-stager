<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPBench\TestUtils;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Symfony\Component\Filesystem\Path as SymfonyPath;
use Symfony\Component\Process\Process as SymfonyProcess;

final class FixtureHelper
{
    private const TEST_ENV = 'var/phpbench';
    private const FIXTURES_DIR = 'fixtures';
    private const WORKING_DIR = 'working-dir';
    private const DRUPAL_ORIGINAL_DIR = 'drupal-09.5.9';
    private const DRUPAL_MAJOR_UPDATE_DIR = 'drupal-10.0.8';
    private const DRUPAL_MINOR_UPDATE_DIR = 'drupal-10.1.0';
    private const DRUPAL_POINT_UPDATE_DIR = 'drupal-10.1.1';
    private const AUTOLOAD_PHP = 'vendor/autoload.php';

    public static function ensureFixtures(): void
    {
        self::ensureCodebase(self::drupalOriginalCodebaseAbsolute());
        self::ensureCodebase(self::drupalMajorUpdateCodebaseAbsolute());
        self::ensureCodebase(self::drupalMinorUpdateCodebaseAbsolute());
        self::ensureCodebase(self::drupalPointUpdateCodebaseAbsolute());
    }

    public static function repositoryRootAbsolute(): string
    {
        return dirname(__DIR__, 3);
    }

    public static function workingDirAbsolute(): string
    {
        return SymfonyPath::makeAbsolute(self::WORKING_DIR, self::testEnvAbsolute());
    }

    public static function drupalOriginalCodebasePath(): PathInterface
    {
        return PathTestHelper::createPath(self::drupalOriginalCodebaseAbsolute());
    }

    public static function drupalMajorUpdateCodebasePath(): PathInterface
    {
        return PathTestHelper::createPath(self::drupalMajorUpdateCodebaseAbsolute());
    }

    public static function drupalMinorUpdateCodebasePath(): PathInterface
    {
        return PathTestHelper::createPath(self::drupalMinorUpdateCodebaseAbsolute());
    }

    public static function drupalPointUpdateCodebasePath(): PathInterface
    {
        return PathTestHelper::createPath(self::drupalPointUpdateCodebaseAbsolute());
    }

    public static function removeWorkingDir(): void
    {
        (new SymfonyFilesystem())->remove(self::workingDirAbsolute());
    }

    private static function testEnvAbsolute(): string
    {
        return SymfonyPath::makeAbsolute(self::TEST_ENV, self::repositoryRootAbsolute());
    }

    private static function fixturesDirAbsolute(): string
    {
        return SymfonyPath::makeAbsolute(
            self::FIXTURES_DIR,
            self::testEnvAbsolute(),
        );
    }

    private static function drupalOriginalCodebaseAbsolute(): string
    {
        return SymfonyPath::makeAbsolute(
            self::DRUPAL_ORIGINAL_DIR,
            self::fixturesDirAbsolute(),
        );
    }

    private static function drupalMajorUpdateCodebaseAbsolute(): string
    {
        return SymfonyPath::makeAbsolute(
            self::DRUPAL_MAJOR_UPDATE_DIR,
            self::fixturesDirAbsolute(),
        );
    }

    private static function drupalMinorUpdateCodebaseAbsolute(): string
    {
        return SymfonyPath::makeAbsolute(
            self::DRUPAL_MINOR_UPDATE_DIR,
            self::fixturesDirAbsolute(),
        );
    }

    private static function drupalPointUpdateCodebaseAbsolute(): string
    {
        return SymfonyPath::makeAbsolute(
            self::DRUPAL_POINT_UPDATE_DIR,
            self::fixturesDirAbsolute(),
        );
    }

    private static function ensureCodebase(string $codebaseDirAbsolute): void
    {
        if (self::codebaseIsReady($codebaseDirAbsolute)) {
            return;
        }

        self::installCodebase($codebaseDirAbsolute);
    }

    private static function codebaseIsReady(string $codebaseDirAbsolute): bool
    {
        return file_exists(SymfonyPath::makeAbsolute(
            self::AUTOLOAD_PHP,
            $codebaseDirAbsolute,
        ));
    }

    private static function installCodebase(string $codebaseDirAbsolute): void
    {
        $process = new SymfonyProcess([
            'composer',
            'install',
            '--ignore-platform-reqs',
            '--no-interaction',
        ], $codebaseDirAbsolute);
        $process->setTimeout(ProcessHelper::PROCESS_TIMEOUT);
        $process->mustRun();
    }
}
