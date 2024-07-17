<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Tests\Translation\Value\TranslatableReflection;
use ReflectionProperty;
use Throwable;

/** Provides custom test assertions. */
trait AssertTrait
{
    protected static function assertArrayEquals(array $expected, array $actual, string $message = ''): void
    {
        // Normalize arrays for comparison.
        $expected = array_filter($expected);
        asort($expected);
        $actual = array_filter($actual);
        asort($actual);

        // Make diffs easier to read by eliminating noise coming from numeric keys.
        $expected = array_fill_keys($expected, 0);
        $actual = array_fill_keys($actual, 0);

        if ($message === '') {
            $message = 'Failed asserting that two arrays are equal';
        }

        self::assertEquals($expected, $actual, $message);
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
        $expected = array_map(PathTestHelper::fixSeparators(...), $expected);

        $actual = self::getFlatDirectoryListing($dir);

        // Remove ignored paths.
        $actual = array_map(static function (string $path) use ($dir, $ignoreDir): bool|string {
            // Paths must be prefixed with the given directory for "ignored paths"
            // matching but returned un-prefixed for later expectation comparison.
            $matchPath = PathTestHelper::ensureTrailingSlash($dir) . $path;
            $ignoreDir = PathTestHelper::ensureTrailingSlash($ignoreDir);

            if (str_starts_with($matchPath, $ignoreDir)) {
                return false;
            }

            return PathTestHelper::fixSeparators($path);
        }, $actual);

        if ($message === '') {
            $message = "Directory {$dir} contains the expected files.";
        }

        self::assertArrayEquals($expected, $actual, $message);
    }

    protected static function assertDirectoriesAreTheSame(
        string $firstDirAbsolute,
        string $secondDirAbsolute,
        $message = '',
    ): void {
        self::assertSame(
            self::getFlatDirectoryListing($firstDirAbsolute),
            self::getFlatDirectoryListing($secondDirAbsolute),
            $message,
        );
    }

    protected static function assertVfsStructureIsSame(array $given, $message = ''): void
    {
        array_multisort($given, SORT_ASC);
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $actual = vfsStream::inspect(new vfsStreamStructureVisitor())
            ->getStructure()['root'];
        array_multisort($actual, SORT_ASC);

        if ($message === '') {
            $message = "Directory contains the expected files.";
        }

        self::assertSame($given, $actual, $message);
    }

    protected static function assertFileMode(string $path, int $mode): void
    {
        if (EnvironmentTestHelper::isWindows()) {
            // Windows doesn't support file permissions. Treat it like a pass and move on.
            self::assertTrue(true, 'Ignore unsupported file permissions on Windows.');

            return;
        }

        assert(FilesystemTestHelper::exists($path), sprintf('File does not exist: %s', $path));

        $actual = FilesystemTestHelper::fileMode($path);

        self::assertOctalEquals($mode, $actual);
    }

    protected static function assertOctalEquals(int $expected, mixed $actual): void
    {
        self::assertSame(
            substr(sprintf('0%o', $expected), -4),
            substr(sprintf('0%o', $actual), -4),
            sprintf('File has expected permissions (0%o).', $expected),
        );
    }

    /** Asserts that a given class is translatable aware. */
    protected static function assertTranslatableAware(object $sut): void
    {
        $reflection = new ReflectionProperty($sut, 'translatableFactory');
        $value = $reflection->getValue($sut);

        $message = sprintf('%s is not translatable aware.', get_debug_type($sut));
        self::assertInstanceOf(TranslatableFactoryInterface::class, $value, $message);
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
     * @param int|null $expectedExceptionCode
     *   The expected code that the exception should return, or null to use zero (0).
     * @param string|null $expectedPreviousExceptionClass
     *   An optional expected "$previous" exception class.
     */
    protected static function assertTranslatableException(
        callable $callback,
        string $expectedExceptionClass,
        TranslatableInterface|string|null $expectedExceptionMessage = null,
        ?int $expectedExceptionCode = 0,
        ?string $expectedPreviousExceptionClass = null,
    ): void {
        try {
            $callback();
        } catch (Throwable $actualException) {
            $actualExceptionClass = $actualException::class;
            $expectedExceptionCode = (int) $expectedExceptionCode;

            if ($actualException instanceof ExceptionInterface) {
                $actualExceptionMessage = $actualException->getTranslatableMessage()->trans();
                $actualExceptionCode = $actualException->getCode();
            } else {
                $actualExceptionMessage = $actualException->getMessage();
                $actualExceptionCode = 0;
            }

            if ($actualExceptionClass !== $expectedExceptionClass || $actualExceptionCode !== $expectedExceptionCode) {
                self::fail(sprintf(
                    'Failed to throw correct exception.'
                    . PHP_EOL . 'Expected:'
                    . PHP_EOL . ' - Class: %s'
                    . PHP_EOL . ' - Message: %s'
                    . PHP_EOL . ' - Code: %s'
                    . PHP_EOL . 'Got:'
                    . PHP_EOL . ' - Class: %s'
                    . PHP_EOL . ' - Message: %s'
                    . PHP_EOL . ' - Code: %s',
                    $expectedExceptionClass,
                    $expectedExceptionMessage,
                    $expectedExceptionCode,
                    $actualExceptionClass,
                    $actualExceptionMessage,
                    $actualExceptionCode,
                ));
            }

            self::assertTrue(true, 'Threw correct exception.');

            if ($expectedExceptionMessage instanceof TranslatableInterface) {
                assert($actualException instanceof ExceptionInterface, sprintf('%s is not a Composer Stager exception class.', $actualException::class));
                self::assertTranslatableEquals(
                    $expectedExceptionMessage,
                    $actualException->getTranslatableMessage(),
                    'Set correct exception message (compared with expected translatable message).',
                );
            }

            if (is_string($expectedExceptionMessage)) {
                self::assertEquals(
                    $expectedExceptionMessage,
                    $actualExceptionMessage,
                    'Set correct exception message (compared with expected string).',
                );
            }

            if ($actualException instanceof ExceptionInterface) {
                $reflection = new TranslatableReflection($actualException->getTranslatableMessage());
                self::assertSame(TranslationTestHelper::DOMAIN_EXCEPTIONS, $reflection->getDomain(), 'Set correct domain.');
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

    /** Asserts that a given translatable message matches expectations. */
    protected static function assertTranslatableMessage(
        string $expected,
        TranslatableInterface $translatable,
        string $message = '',
    ): void {
        self::assertSame($expected, $translatable->trans(), $message);
    }
}
