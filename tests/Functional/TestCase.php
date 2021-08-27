<?php

namespace PhpTuf\ComposerStager\Tests\Functional;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected const TEST_ENV = __DIR__ . '/../../var/phpunit/test-env';
    protected const ACTIVE_DIR = 'active-dir';
    protected const STAGING_DIR = 'staging-dir';

    protected static function createTestEnvironment(): void
    {
        $filesystem = new Filesystem();

        // Create the test environment and cd into it,
        $filesystem->mkdir(self::TEST_ENV);
        chdir(self::TEST_ENV);

        // Create the active directory only. The staging directory is created
        // when the "begin" command is exercised.
        $filesystem->mkdir(self::ACTIVE_DIR);
    }

    protected static function removeTestEnvironment(): void
    {
        $filesystem = new Filesystem();
        if ($filesystem->exists(self::TEST_ENV)) {
            try {
                $filesystem->remove(self::TEST_ENV);
            } catch (IOException $e) {
                // @todo Windows chokes on this every time, e.g.,
                //    | Failed to remove directory
                //    | "D:\a\composer-stager\composer-stager\tests\Functional/../../var/phpunit/test-env":
                //    | rmdir(D:\a\composer-stager\composer-stager\tests\Functional/../../var/phpunit/test-env):
                //    | Resource temporarily unavailable.
                //   Obviously, this error suppression is likely to bite us in the future
                //   even though it doesn't seem to cause any problems now. Fix it.
            }
        }
    }

    protected static function getContainer(): Container
    {
        $container = new ContainerBuilder();

        $loader = new YamlFileLoader($container, new FileLocator());
        $loader->load(__DIR__ . '/../../config/services.yml');

        $container->compile();

        return $container;
    }

    protected static function initializeComposerJson(): void
    {
        $process = new Process([
            'composer',
            '--working-dir=' . self::ACTIVE_DIR,
            'init',
            '--name=lorem/ipsum',
            '--no-interaction',
        ]);
        $process->mustRun();
    }

    protected static function runFrontScript(array $args, string $cwd = __DIR__): Process
    {
        $command = array_merge([
            'bin' => 'php',
            'scriptPath' => realpath(__DIR__ . '/../../bin/composer-stage'),
        ], $args);
        $process = new Process($command, $cwd);
        $process->mustRun();
        return $process;
    }

    protected static function createFiles(array $filenames): void
    {
        foreach ($filenames as $filename) {
            $dirname = dirname($filename);
            if (!file_exists($dirname)) {
                self::assertTrue(mkdir($dirname, 0777, true), 'Created directory.');
            }
            self::assertTrue(touch($filename), 'Created file.');
            self::assertNotFalse(realpath($filename), 'Got absolute path.');
        }
    }

    protected static function assertStagingDirectoryDoesNotExist(): void
    {
        self::assertFileDoesNotExist(self::STAGING_DIR, 'Staging directory does not exist.');
    }

    protected static function assertActiveAndStagingDirectoriesSame(): void
    {
        self::assertSame(
            '',
            self::getActiveAndStagingDirectoriesDiff(),
            'Active and staging directories are the same.'
        );
    }

    protected static function assertActiveAndStagingDirectoriesNotSame(): void
    {
        self::assertNotSame(
            '',
            self::getActiveAndStagingDirectoriesDiff(),
            'Active and staging directories are not the same.'
        );
    }

    protected static function getActiveAndStagingDirectoriesDiff(): string
    {
        $process = new Process([
            'diff',
            '--recursive',
            self::ACTIVE_DIR,
            self::STAGING_DIR,
        ]);
        $process->run();
        return $process->getOutput();
    }
}
