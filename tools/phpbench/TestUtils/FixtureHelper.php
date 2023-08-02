<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPBench\TestUtils;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory;
use Symfony\Component\Filesystem\Path as SymfonyPath;
use Symfony\Component\Process\Process as SymfonyProcess;

/** @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps */
final class FixtureHelper
{
    private const REPOSITORY_ROOT = '../../..';
    private const TEST_ENV = 'var/phpbench';
    private const FIXTURES_DIR = 'fixtures';
    private const WORKING_DIR = 'working-dir';
    private const DRUPAL_9_5_DIR = 'drupal-9.5';
    private const DRUPAL_10_0_DIR = 'drupal-10.0';
    private const DRUPAL_10_1_DIR = 'drupal-10.1';
    private const AUTOLOAD_PHP = 'vendor/autoload.php';

    public static function ensureFixtures(): void
    {
        self::ensureCodebase(self::drupal_9_5_CodebaseAbsolute());
        self::ensureCodebase(self::drupal_10_0_CodebaseAbsolute());
        self::ensureCodebase(self::drupal_10_1_CodebaseAbsolute());
    }

    public static function repositoryRootAbsolute(): string
    {
        return SymfonyPath::makeAbsolute(self::REPOSITORY_ROOT, __DIR__);
    }

    public static function drupal_9_5_CodebasePath(): PathInterface
    {
        return PathFactory::create(self::drupal_9_5_CodebaseAbsolute());
    }

    public static function drupal_10_0_CodebasePath(): PathInterface
    {
        return PathFactory::create(self::drupal_10_0_CodebaseAbsolute());
    }

    public static function drupal_10_1_CodebasePath(): PathInterface
    {
        return PathFactory::create(self::drupal_10_1_CodebaseAbsolute());
    }

    public static function workingDirAbsolute(): string
    {
        return SymfonyPath::makeAbsolute(self::WORKING_DIR, self::testEnvAbsolute());
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

    private static function drupal_9_5_CodebaseAbsolute(): string
    {
        return SymfonyPath::makeAbsolute(
            self::DRUPAL_9_5_DIR,
            self::fixturesDirAbsolute(),
        );
    }

    private static function drupal_10_0_CodebaseAbsolute(): string
    {
        return SymfonyPath::makeAbsolute(
            self::DRUPAL_10_0_DIR,
            self::fixturesDirAbsolute(),
        );
    }

    private static function drupal_10_1_CodebaseAbsolute(): string
    {
        return SymfonyPath::makeAbsolute(
            self::DRUPAL_10_1_DIR,
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
