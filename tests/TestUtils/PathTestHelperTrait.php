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

    /**
     * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
     *
     * @phpcs:disable SlevomatCodingStandard.PHP.DisallowReference.DisallowedPassingByReference
     */
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
