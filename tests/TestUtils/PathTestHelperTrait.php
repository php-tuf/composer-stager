<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

use PhpTuf\ComposerStager\API\Path\Factory\PathListFactoryInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory;
use PhpTuf\ComposerStager\Internal\Path\Service\PathHelperInterface;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper as Helper;

/**
 * Provides convenience methods for PathTestHelper calls.
 *
 * @see \PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper
 */
trait PathTestHelperTrait
{
    protected static function repositoryRootAbsolute(): string
    {
        return Helper::repositoryRootAbsolute();
    }

    protected static function testEnvAbsolute(): string
    {
        return Helper::testEnvAbsolute();
    }

    protected static function testFreshFixturesDirAbsolute(): string
    {
        return Helper::testFreshFixturesDirAbsolute();
    }

    protected static function testPersistentFixturesAbsolute(): string
    {
        return Helper::testPersistentFixturesAbsolute();
    }

    protected static function activeDirRelative(): string
    {
        return Helper::activeDirRelative();
    }

    protected static function activeDirAbsolute(): string
    {
        return Helper::activeDirAbsolute();
    }

    protected static function activeDirPath(): PathInterface
    {
        return Helper::activeDirPath();
    }

    protected static function stagingDirRelative(): string
    {
        return Helper::stagingDirRelative();
    }

    protected static function stagingDirAbsolute(): string
    {
        return Helper::stagingDirAbsolute();
    }

    protected static function stagingDirPath(): PathInterface
    {
        return Helper::stagingDirPath();
    }

    protected static function sourceDirRelative(): string
    {
        return Helper::sourceDirRelative();
    }

    protected static function sourceDirAbsolute(): string
    {
        return Helper::sourceDirAbsolute();
    }

    protected static function sourceDirPath(): PathInterface
    {
        return Helper::sourceDirPath();
    }

    protected static function destinationDirRelative(): string
    {
        return Helper::destinationDirRelative();
    }

    protected static function destinationDirAbsolute(): string
    {
        return Helper::destinationDirAbsolute();
    }

    protected static function destinationDirPath(): PathInterface
    {
        return Helper::destinationDirPath();
    }

    protected static function arbitraryDirRelative(): string
    {
        return Helper::arbitraryDirRelative();
    }

    protected static function arbitraryDirAbsolute(): string
    {
        return Helper::arbitraryDirAbsolute();
    }

    protected static function arbitraryDirPath(): PathInterface
    {
        return Helper::arbitraryDirPath();
    }

    protected static function arbitraryFileRelative(): string
    {
        return Helper::arbitraryFileRelative();
    }

    protected static function arbitraryFileAbsolute(): string
    {
        return Helper::arbitraryFileAbsolute();
    }

    protected static function arbitraryFilePath(): PathInterface
    {
        return Helper::arbitraryFilePath();
    }

    protected static function nonExistentDirRelative(): string
    {
        return Helper::nonExistentDirRelative();
    }

    protected static function nonExistentDirAbsolute(): string
    {
        return Helper::nonExistentDirAbsolute();
    }

    protected static function nonExistentDirPath(): PathInterface
    {
        return Helper::nonExistentDirPath();
    }

    protected static function nonExistentFileRelative(): string
    {
        return Helper::nonExistentFileRelative();
    }

    protected static function nonExistentFileAbsolute(): string
    {
        return Helper::nonExistentFileAbsolute();
    }

    protected static function nonExistentFilePath(): PathInterface
    {
        return Helper::nonExistentFilePath();
    }

    protected static function createPath(string $path, ?string $basePath = null): PathInterface
    {
        return Helper::createPath($path, $basePath);
    }

    protected static function createPathFactory(): PathFactory
    {
        return Helper::createPathFactory();
    }

    protected static function createPathHelper(): PathHelperInterface
    {
        return Helper::createPathHelper();
    }

    protected static function createPathList(string ...$paths): PathListInterface
    {
        return Helper::createPathList(...$paths);
    }

    protected static function createPathListFactory(): PathListFactoryInterface
    {
        return Helper::createPathListFactory();
    }

    protected static function canonicalize(string $path): string
    {
        return Helper::canonicalize($path);
    }

    protected static function isAbsolute(string $path): bool
    {
        return Helper::isAbsolute($path);
    }

    protected static function makeAbsolute(string $path, ?string $basePath = null): string
    {
        return Helper::makeAbsolute($path, $basePath);
    }

    protected static function fixSeparatorsMultiple(&...$paths): void
    {
        Helper::fixSeparatorsMultiple(...$paths);
    }

    protected static function fixSeparators(string $path): string
    {
        return Helper::fixSeparators($path);
    }

    protected static function ensureTrailingSlash(string $path): string
    {
        return Helper::ensureTrailingSlash($path);
    }

    protected static function stripTrailingSlash(string $path): string
    {
        return Helper::stripTrailingSlash($path);
    }
}
