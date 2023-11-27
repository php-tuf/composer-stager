<?php declare(strict_types=1);

/** @file Provides built-in function mocks for {@see \PhpTuf\ComposerStager\Tests\Path\Value\PathUnitTestCase} */

namespace PhpTuf\ComposerStager\Internal\Path\Value;

use PhpTuf\ComposerStager\Tests\Path\Value\PathUnitTestCase;

function getcwd(): string|false
{
    return PathUnitTestCase::$chmodSpy
        ->reveal()
        ->report();
}

function md5(): string
{
    return PathUnitTestCase::$md5Spy
        ->reveal()
        ->report();
}
