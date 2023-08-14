<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;
use PhpTuf\ComposerStager\Tests\Precondition\Service\TestPrecondition;
use PhpTuf\ComposerStager\Tests\TestUtils\AssertTrait;
use PhpTuf\ComposerStager\Tests\TestUtils\Domain;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableMessage;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

abstract class TestCase extends PHPUnitTestCase
{
    use AssertTrait;
    use ProphecyTrait;

    protected const ORIGINAL_CONTENT = '';
    protected const CHANGED_CONTENT = 'changed';

    protected PathListInterface $exclusions;

    public function container(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator());
        $config = PathHelper::makeAbsolute('config/services.yml', PathHelper::repositoryRootAbsolute());
        $loader->load($config);

        return $container;
    }

    protected static function createTestEnvironment(?string $activeDir = null): void
    {
        $activeDir ??= PathHelper::activeDirRelative();

        self::removeTestEnvironment();

        // Create the active directory only. The staging directory is created
        // when the "begin" command is exercised.
        $workingDirAbsolute = PathHelper::testWorkingDirAbsolute();
        $activeDirAbsolute = PathHelper::makeAbsolute($activeDir, $workingDirAbsolute);
        FilesystemHelper::createDirectories([$workingDirAbsolute, $activeDirAbsolute]);
        chdir($workingDirAbsolute);
    }

    protected static function removeTestEnvironment(): void
    {
        $filesystem = new Filesystem();

        if (!$filesystem->exists(PathHelper::testEnvAbsolute())) {
            return;
        }

        try {
            $filesystem->remove(PathHelper::testEnvAbsolute());
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

    protected static function createFiles(string $basePath, array $filenames): void
    {
        foreach ($filenames as $filename) {
            self::createFile($basePath, $filename);
        }
    }

    protected static function createFile(string $basePath, string $filename): void
    {
        $filename = PathHelper::makeAbsolute($filename, $basePath);
        FilesystemHelper::touch($filename);
        $realpathResult = realpath($filename);

        assert($realpathResult !== false, "Got absolute path of {$filename}.");
    }

    public static function createTestPreconditionException(
        string $message = '',
        ?TranslationParametersInterface $parameters = null,
        $domain = Domain::EXCEPTIONS,
    ): PreconditionException {
        return new PreconditionException(
            new TestPrecondition(),
            new TestTranslatableMessage(
                $message,
                $parameters,
                $domain,
            ),
        );
    }

    protected static function createSymlinks(string $basePath, array $symlinks): void
    {
        foreach ($symlinks as $link => $target) {
            self::createSymlink($basePath, $link, $target);
        }
    }

    protected static function createSymlink(string $basePath, string $link, string $target): void
    {
        $link = PathHelper::createPath($link, $basePath);
        $target = PathHelper::createPath($target, $basePath);

        self::prepareForLink($link, $target);

        symlink($target->absolute(), $link->absolute());
    }

    protected static function createHardlinks(string $basePath, array $symlinks): void
    {
        foreach ($symlinks as $link => $target) {
            self::createHardlink($basePath, $link, $target);
        }
    }

    protected static function createHardlink(string $basePath, string $link, string $target): void
    {
        $link = PathHelper::createPath($link, $basePath);
        $target = PathHelper::createPath($target, $basePath);

        self::prepareForLink($link, $target);

        link($target->absolute(), $link->absolute());
    }

    private static function prepareForLink(PathInterface $link, PathInterface $target): void
    {
        FilesystemHelper::ensureParentDirectory($link->absolute());

        // If the symlink target doesn't exist, the tests will pass on Unix-like
        // systems but fail on Windows. Avoid hard-to-debug problems by making
        // sure it fails everywhere in that case.
        assert(file_exists($target->absolute()), 'Symlink target exists.');
    }

    protected static function changeFile(string $dir, string $filename): void
    {
        $pathname = self::ensureTrailingSlash($dir) . $filename;
        $result = file_put_contents($pathname, self::CHANGED_CONTENT);
        assert($result !== false, "Changed file {$pathname}.");
    }

    protected static function deleteFile(string $dir, string $filename): void
    {
        $pathname = self::ensureTrailingSlash($dir) . $filename;
        $result = unlink($pathname);
        assert($result, "Deleted file {$pathname}.");
    }

    protected static function fixSeparators(string $path): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    /** @phpcs:disable SlevomatCodingStandard.PHP.DisallowReference.DisallowedPassingByReference */
    protected static function fixSeparatorsMultiple(&...$paths): void
    {
        foreach ($paths as &$path) {
            $path = self::fixSeparators($path);
        }
    }

    protected static function getDirectoryContents(string $dir): array
    {
        $dir = self::ensureTrailingSlash($dir);
        $dirListing = self::getFlatDirectoryListing($dir);

        $contents = [];

        foreach ($dirListing as $pathname) {
            if (is_link($dir . $pathname)) {
                $contents[$pathname] = '';

                continue;
            }

            $contents[$pathname] = file_get_contents($dir . $pathname);
        }

        return $contents;
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
    protected static function stripTrailingSlash(string $path): string
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

    /**
     * Ensures that the given path ends with a slash (directory separator).
     *
     * @param string $path
     *   Any path, absolute or relative, existing or not.
     */
    protected static function ensureTrailingSlash(string $path): string
    {
        if ($path === '') {
            $path = '.';
        }

        return self::stripTrailingSlash($path) . DIRECTORY_SEPARATOR;
    }
}
