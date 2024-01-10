<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

use org\bovigo\vfs\vfsStream;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Internal\Path\Value\Path;

final class VfsHelper
{
    private const ROOT_DIR = 'vfs://root/';
    private const ACTIVE_DIR = 'active-dir';
    private const STAGING_DIR = 'staging-dir';
    private const SOURCE_DIR = 'source-dir';
    private const DESTINATION_DIR = 'destination-dir';
    private const ARBITRARY_DIR = 'arbitrary-dir';
    private const ARBITRARY_FILE = 'arbitrary-file.txt';
    private const NON_EXISTENT_DIR = 'non-existent-dir';
    private const NON_EXISTENT_FILE = 'non-existent-file.txt';

    public static function setup(): void
    {
        vfsStream::setup();
    }

    public static function createPath(string $path, string $basePath = self::ROOT_DIR): PathInterface
    {
        return new Path($path, new Path(self::ROOT_DIR));
    }

    public static function activeDirRelative(): string
    {
        return self::ACTIVE_DIR;
    }

    public static function activeDirAbsolute(): string
    {
        return self::makeAbsolute(self::activeDirRelative());
    }

    public static function activeDirPath(): PathInterface
    {
        return self::createPath(self::activeDirAbsolute());
    }

    public static function stagingDirRelative(): string
    {
        return self::STAGING_DIR;
    }

    public static function stagingDirAbsolute(): string
    {
        return self::makeAbsolute(self::stagingDirRelative());
    }

    public static function stagingDirPath(): PathInterface
    {
        return self::createPath(self::stagingDirAbsolute());
    }

    public static function sourceDirRelative(): string
    {
        return self::SOURCE_DIR;
    }

    public static function sourceDirAbsolute(): string
    {
        return self::makeAbsolute(self::sourceDirRelative());
    }

    public static function sourceDirPath(): PathInterface
    {
        return self::createPath(self::sourceDirAbsolute());
    }

    public static function destinationDirRelative(): string
    {
        return self::DESTINATION_DIR;
    }

    public static function destinationDirAbsolute(): string
    {
        return self::makeAbsolute(self::destinationDirRelative());
    }

    public static function destinationDirPath(): PathInterface
    {
        return self::createPath(self::destinationDirAbsolute());
    }

    public static function arbitraryDirRelative(): string
    {
        return self::ARBITRARY_DIR;
    }

    public static function arbitraryDirAbsolute(): string
    {
        return self::makeAbsolute(self::arbitraryDirRelative());
    }

    public static function arbitraryDirPath(): PathInterface
    {
        return self::createPath(self::arbitraryDirAbsolute());
    }

    public static function arbitraryFileRelative(): string
    {
        return self::ARBITRARY_FILE;
    }

    public static function arbitraryFileAbsolute(): string
    {
        return self::makeAbsolute(self::arbitraryFileRelative());
    }

    public static function arbitraryFilePath(): PathInterface
    {
        return self::createPath(self::arbitraryFileAbsolute());
    }

    public static function nonExistentDirRelative(): string
    {
        return self::NON_EXISTENT_DIR;
    }

    public static function nonExistentDirAbsolute(): string
    {
        return self::makeAbsolute(self::nonExistentDirRelative());
    }

    public static function nonExistentDirPath(): PathInterface
    {
        return self::createPath(self::nonExistentDirAbsolute());
    }

    public static function nonExistentFileRelative(): string
    {
        return self::NON_EXISTENT_FILE;
    }

    public static function nonExistentFileAbsolute(): string
    {
        return self::makeAbsolute(self::nonExistentFileRelative());
    }

    public static function nonExistentFilePath(): PathInterface
    {
        return self::createPath(self::nonExistentFileAbsolute());
    }

    private static function makeAbsolute(string $path): string
    {
        return self::ROOT_DIR . $path;
    }
}
