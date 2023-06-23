<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests;

use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Translation\Value\Domain;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory;
use PhpTuf\ComposerStager\Tests\Precondition\Service\TestPrecondition;
use PhpTuf\ComposerStager\Tests\Translation\Service\TestTranslator;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableMessage;
use PhpTuf\ComposerStager\Tests\Translation\Value\TranslatableReflection;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

abstract class TestCase extends PHPUnitTestCase
{
    use ProphecyTrait;

    protected const PROJECT_ROOT = __DIR__ . '/..';
    protected const TEST_ENV = self::PROJECT_ROOT . '/var/phpunit/test-env';
    protected const TEST_WORKING_DIR = self::TEST_ENV . '/working-dir';
    protected const ACTIVE_DIR = 'active-dir';
    protected const STAGING_DIR = 'staging-dir';
    protected const ORIGINAL_CONTENT = '';
    protected const CHANGED_CONTENT = 'changed';

    protected static function testWorkingDirPath(): PathInterface
    {
        return PathFactory::create(self::TEST_WORKING_DIR);
    }

    protected static function activeDirPath(): PathInterface
    {
        return PathFactory::create(self::ACTIVE_DIR, self::testWorkingDirPath());
    }

    protected static function stagingDirPath(): PathInterface
    {
        return PathFactory::create(self::STAGING_DIR, self::testWorkingDirPath());
    }

    public function getContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator());
        $loader->load(self::PROJECT_ROOT . '/config/services.yml');

        return $container;
    }

    protected static function createTestEnvironment(string $activeDir = self::ACTIVE_DIR): void
    {
        self::removeTestEnvironment();

        // Create the test environment.
        mkdir(self::TEST_WORKING_DIR, 0777, true);
        chdir(self::TEST_WORKING_DIR);

        // Create the active directory only. The staging directory is created
        // when the "begin" command is exercised.
        mkdir($activeDir, 0777, true);
    }

    protected static function removeTestEnvironment(): void
    {
        $filesystem = new Filesystem();

        if (!$filesystem->exists(self::TEST_ENV)) {
            return;
        }

        try {
            $filesystem->remove(self::TEST_ENV);
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

    protected static function createFiles(string $baseDir, array $filenames): void
    {
        foreach ($filenames as $filename) {
            self::createFile($baseDir, $filename);
        }
    }

    protected static function createFile(string $baseDir, string $filename): void
    {
        $filename = PathFactory::create("{$baseDir}/{$filename}")->resolved();
        static::ensureParentDirectory($filename);

        $touchResult = touch($filename);
        $realpathResult = realpath($filename);

        assert($touchResult, "Created file {$filename}.");
        assert($realpathResult !== false, "Got absolute path of {$filename}.");
    }

    protected static function createDirectories(string $baseDir, array $dirnames): void
    {
        foreach ($dirnames as $dirname) {
            self::createDirectory($baseDir, $dirname);
        }
    }

    protected static function createDirectory(string $baseDir, string $dirname): void
    {
        $dirname = PathFactory::create("{$baseDir}/{$dirname}")->resolved();
        static::ensureParentDirectory($dirname);

        $touchResult = mkdir($dirname);
        $realpathResult = realpath($dirname);

        assert($touchResult, "Created directory {$dirname}.");
        assert($realpathResult !== false, "Got absolute path of {$dirname}.");
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

    protected static function createSymlinks(string $baseDir, array $symlinks): void
    {
        foreach ($symlinks as $link => $target) {
            self::createSymlink($baseDir, $link, $target);
        }
    }

    protected static function createSymlink(string $baseDir, string $link, string $target): void
    {
        $link = PathFactory::create("{$baseDir}/{$link}");
        $target = PathFactory::create("{$baseDir}/{$target}");

        self::prepareForLink($link, $target);

        symlink($target->resolved(), $link->resolved());
    }

    protected static function createHardlinks(string $baseDir, array $symlinks): void
    {
        foreach ($symlinks as $link => $target) {
            self::createHardlink($baseDir, $link, $target);
        }
    }

    protected static function createHardlink(string $baseDir, string $link, string $target): void
    {
        $link = PathFactory::create("{$baseDir}/{$link}");
        $target = PathFactory::create("{$baseDir}/{$target}");

        self::prepareForLink($link, $target);

        link($target->resolved(), $link->resolved());
    }

    private static function prepareForLink(PathInterface $link, PathInterface $target): void
    {
        static::ensureParentDirectory($link->resolved());

        // If the symlink target doesn't exist, the tests will pass on Unix-like
        // systems but fail on Windows. Avoid hard-to-debug problems by making
        // sure it fails everywhere in that case.
        assert(file_exists($target->resolved()), 'Symlink target exists.');
    }

    protected static function ensureParentDirectory(string $filename): void
    {
        $dirname = dirname($filename);

        if (file_exists($dirname)) {
            return;
        }

        $mkdirResult = mkdir($dirname, 0777, true);
        assert($mkdirResult, "Created directory {$dirname}.");
    }

    protected static function changeFile(string $dir, $filename): void
    {
        $pathname = self::ensureTrailingSlash($dir) . $filename;
        $result = file_put_contents($pathname, self::CHANGED_CONTENT);
        assert($result !== false, "Changed file {$pathname}.");
    }

    protected static function deleteFile(string $dir, $filename): void
    {
        $pathname = self::ensureTrailingSlash($dir) . $filename;
        $result = unlink($pathname);
        assert($result, "Deleted file {$pathname}.");
    }

    protected static function fixSeparators(string $path): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    /** phpcs:disable SlevomatCodingStandard.PHP.DisallowReference.DisallowedPassingByReference */
    protected static function fixSeparatorsMultiple(&...$paths): void
    {
        foreach ($paths as &$path) {
            $path = self::fixSeparators($path);
        }
    }

    /**
     * Asserts a flattened directory listing similar to what GNU find would
     * return, alphabetized for easier comparison. Example:
     * ```php
     * [
     *     'eight.txt',
     *     'four/five.txt',
     *     'one/two/three.txt',
     *     'six/seven.txt',
     * ];
     * ```
     */
    protected static function assertDirectoryListing(
        string $dir,
        array $expected,
        string $ignoreDir = '',
        string $message = '',
    ): void {
        $expected = array_map(self::fixSeparators(...), $expected);

        $actual = self::getFlatDirectoryListing($dir);

        // Remove ignored paths.
        $actual = array_map(static function ($path) use ($dir, $ignoreDir): bool|string {
            // Paths must be prefixed with the given directory for "ignored paths"
            // matching but returned un-prefixed for later expectation comparison.
            $matchPath = self::ensureTrailingSlash($dir) . $path;
            $ignoreDir = self::ensureTrailingSlash($ignoreDir);

            if (str_starts_with($matchPath, $ignoreDir)) {
                return false;
            }

            return self::fixSeparators($path);
        }, $actual);

        // Normalize arrays for comparison.
        $expected = array_filter($expected);
        asort($expected);
        $actual = array_filter($actual);
        asort($actual);

        // Make diffs easier to read by eliminating noise coming from numeric keys.
        $expected = array_fill_keys($expected, 0);
        $actual = array_fill_keys($actual, 0);

        if ($message === '') {
            $message = "Directory {$dir} contains the expected files.";
        }

        self::assertEquals($expected, $actual, $message);
    }

    /**
     * Asserts that a callback throws a specific exception.
     *
     * @param callable $callback
     *   The callback that exercises the SUT.
     * @param string $expectedExceptionClass
     *   The expected exception class name, e.g., \Exception::class.
     * @param \PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface|string|null $expectedExceptionMessage
     *   The expected message that the exception should return,
     *   both raw and translatable, or null to ignore the message.
     * @param string|null $expectedPreviousExceptionClass
     *   An optional expected "$previous" exception class.
     */
    protected static function assertTranslatableException(
        callable $callback,
        string $expectedExceptionClass,
        TranslatableInterface|string|null $expectedExceptionMessage = null,
        ?string $expectedPreviousExceptionClass = null,
    ): void {
        try {
            $callback();
        } catch (Throwable $actualException) {
            $actualExceptionMessage = $actualException instanceof ExceptionInterface
                ? $actualException->getTranslatableMessage()->trans(new TestTranslator())
                : $actualException->getMessage();

            if ($actualException::class !== $expectedExceptionClass) {
                self::fail(sprintf(
                    'Failed to throw correct exception.'
                    . PHP_EOL . 'Expected:'
                    . PHP_EOL . ' - Class: %s'
                    . PHP_EOL . ' - Message: %s'
                    . PHP_EOL . 'Got:'
                    . PHP_EOL . ' - Class: %s'
                    . PHP_EOL . ' - Message: %s',
                    $expectedExceptionClass,
                    $expectedExceptionMessage,
                    $actualException::class,
                    $actualExceptionMessage,
                ));
            }

            self::assertTrue(true, 'Threw correct exception.');

            if ($expectedExceptionMessage instanceof TranslatableInterface) {
                assert($actualException instanceof ExceptionInterface);
                self::assertTranslatableEquals(
                    $expectedExceptionMessage,
                    $actualException->getTranslatableMessage(),
                    'Set correct exception message.',
                );
            } elseif (is_string($expectedExceptionMessage)) {
                self::assertEquals(
                    $expectedExceptionMessage,
                    $actualExceptionMessage,
                    'Set correct exception message.',
                );
            }

            if ($actualException instanceof ExceptionInterface) {
                $x = new TranslatableReflection($actualException->getTranslatableMessage());
                self::assertSame(Domain::EXCEPTIONS, $x->getDomain(), 'Set correct domain.');
            }

            if ($expectedPreviousExceptionClass === null) {
                return;
            }

            $actualPreviousException = $actualException->getPrevious();
            $actualPreviousExceptionClass = $actualPreviousException instanceof Throwable
                ? $actualPreviousException::class
                : 'none';
            $actualPreviousExceptionMessage = $actualPreviousException instanceof Throwable
                ? $actualPreviousException->getMessage()
                : 'n/a';

            if ($expectedPreviousExceptionClass !== $actualPreviousExceptionClass) {
                self::fail(sprintf(
                    'Failed re-throw previous exception.'
                    . PHP_EOL . 'Expected:'
                    . PHP_EOL . ' - Class: %s'
                    . PHP_EOL . 'Got:'
                    . PHP_EOL . ' - Class: %s'
                    . PHP_EOL . ' - Message: %s',
                    $expectedPreviousExceptionClass,
                    $actualPreviousExceptionClass,
                    $actualPreviousExceptionMessage,
                ));
            }

            self::assertTrue(true, 'Correctly re-threw previous exception.');

            return;
        }

        self::fail(sprintf('Failed to throw any exception. Expected %s.', $expectedExceptionClass));
    }

    /** Asserts that two translatables are equivalent, i.e., have the same properties. */
    protected static function assertTranslatableEquals(
        TranslatableInterface $expected,
        TranslatableInterface $actual,
        string $message = '',
    ): void {
        $expected = new TranslatableReflection($expected);
        $actual = new TranslatableReflection($actual);
        self::assertSame($expected->getProperties(), $actual->getProperties(), $message);
    }

    /** Asserts that a given translatable message matches expectations. */
    public static function assertTranslatableMessage(
        string $expected,
        TranslatableInterface $translatable,
        string $message = '',
    ): void {
        self::assertSame($expected, $translatable->trans(new TestTranslator()), $message);
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
     * Returns a flattened directory listing similar to what GNU find would,
     * alphabetized for easier comparison. Example:
     * ```php
     * [
     *     'eight.txt',
     *     'four/five.txt',
     *     'one/two/three.txt',
     *     'six/seven.txt',
     * ];
     * ```
     */
    protected static function getFlatDirectoryListing(string $dir): array
    {
        $dir = self::stripTrailingSlash($dir);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir),
        );

        $listing = [];

        foreach ($iterator as $splFileInfo) {
            assert($splFileInfo instanceof SplFileInfo);

            if (in_array($splFileInfo->getFilename(), ['.', '..'], true)) {
                continue;
            }

            $pathname = $splFileInfo->getPathname();
            $listing[] = substr($pathname, strlen($dir) + 1);
        }

        sort($listing);

        return array_values($listing);
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
