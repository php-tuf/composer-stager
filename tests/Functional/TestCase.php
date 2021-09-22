<?php

namespace PhpTuf\ComposerStager\Tests\Functional;

use PhpTuf\ComposerStager\Util\DirectoryUtil;
use RecursiveIteratorIterator;
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
    protected const ORIGINAL_CONTENT = '';
    protected const CHANGED_CONTENT = 'changed';

    protected static function createTestEnvironment(string $activeDir): void
    {
        $filesystem = new Filesystem();

        // Create the test environment,
        $filesystem->mkdir(self::TEST_ENV);
        chdir(self::TEST_ENV);

        // Create the active directory only. The staging directory is created
        // when the "begin" command is exercised.
        $filesystem->mkdir($activeDir);
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

    protected static function createFiles(string $baseDir, array $filenames): void
    {
        foreach ($filenames as $filename) {
            self::createFile($baseDir, $filename);
        }
    }

    protected static function createFile(string $baseDir, string $filename): void
    {
        $filename = "{$baseDir}/{$filename}";
        $dirname = dirname($filename);
        if (!file_exists($dirname)) {
            self::assertTrue(mkdir($dirname, 0777, true), "Created directory {$dirname}.");
        }
        self::assertTrue(touch($filename), "Created file {$filename}.");
        self::assertNotFalse(realpath($filename), "Got absolute path of {$filename}.");
    }

    protected static function changeFile($dir, $filename): void
    {
        $pathname = DirectoryUtil::ensureTrailingSlash($dir) . $filename;
        $result = file_put_contents(
            $pathname,
            self::CHANGED_CONTENT
        );
        self::assertNotFalse($result, "Changed file {$pathname}.");
    }

    protected static function deleteFile($dir, $filename): void
    {
        $pathname = DirectoryUtil::ensureTrailingSlash($dir) . $filename;
        $result = unlink($pathname);
        self::assertTrue($result, "Deleted file {$pathname}.");
    }

    protected static function fixSeparators(string $path): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    protected static function fixSeparatorsMultiple(&...$paths): void
    {
        foreach ($paths as &$path) {
            $path = self::fixSeparators($path);
        }
    }

    protected static function assertDirectoryListing(string $dir, array $expected, string $ignoreDir = ''): void
    {
        $ignoreDir = DirectoryUtil::stripAncestor($ignoreDir, $dir);

        $expected = array_map([self::class, 'fixSeparators'], $expected);

        $expected = array_filter($expected);
        asort($expected);
        $expected = array_values($expected);

        $actual = self::getFlatDirectoryListing($dir);
        $actual = array_map(static function ($path) use ($ignoreDir) {
            $ignoreDir = DirectoryUtil::ensureTrailingSlash($ignoreDir);
            if (strpos($path, $ignoreDir) === 0) {
                return false;
            }
            return self::fixSeparators($path);
        }, $actual);

        $actual = array_filter($actual);
        asort($actual);
        $actual = array_values($actual);

        self::assertEquals($expected, $actual, "Directory {$dir} contains the expected files.");
    }

    /**
     * Returns a flattened directory listing similar to what GNU find would,
     * alphabetized for easier comparison. Example:
     * ```php
     * [
     *     'adipiscing.txt',
     *     'lorem/ipsum/dolor.txt',
     *     'sit/amet.txt',
     *     'sit/consectetur.txt',
     * ];
     * ```
     */
    protected static function getFlatDirectoryListing(string $dir): array
    {
        $dir = DirectoryUtil::stripTrailingSlash($dir);

        $iterator = new RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir)
        );

        $listing = [];
        /** @var \SplFileInfo $splFileInfo */
        foreach ($iterator as $splFileInfo) {
            if (in_array($splFileInfo->getFilename(), ['.', '..'])) {
                continue;
            }
            $pathname = $splFileInfo->getPathname();
            $listing[] = substr($pathname, strlen($dir) + 1);
        }
        sort($listing);
        return array_values($listing);
    }

    protected static function assertFileChanged($dir, $path, $message = ''): void
    {
        self::assertStringEqualsFile(
            DirectoryUtil::ensureTrailingSlash($dir) . $path,
            self::CHANGED_CONTENT,
            $message
        );
    }

    protected static function assertFileNotChanged($dir, $path, $message = ''): void
    {
        self::assertStringEqualsFile(
            DirectoryUtil::ensureTrailingSlash($dir) . $path,
            self::ORIGINAL_CONTENT,
            $message
        );
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
