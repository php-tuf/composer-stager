<?php declare(strict_types=1);

/** @file Provides built-in function mocks for {@see \PhpTuf\ComposerStager\Tests\Filesystem\Service\FilesystemUnitTest} */

namespace PhpTuf\ComposerStager\Internal\Filesystem\Service;

use PhpTuf\ComposerStager\Tests\Filesystem\Service\FilesystemUnitTest;

function chmod(string $filename, int $permissions): bool
{
    return FilesystemUnitTest::$chmodSpy
        ->reveal()
        ->report($filename, $permissions);
}

function fileperms(string $filename): int|false
{
    return FilesystemUnitTest::$filePermsSpy
        ->reveal()
        ->report($filename);
}
