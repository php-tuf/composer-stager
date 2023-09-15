<?php declare(strict_types=1);

/** @file Provides built-in function mocks for {@see \PhpTuf\ComposerStager\Tests\Environment\Service\EnvironmentUnitTest} */

namespace PhpTuf\ComposerStager\Internal\Environment\Service;

use PhpTuf\ComposerStager\Tests\Environment\Service\EnvironmentUnitTest;

function function_exists(string $function): bool
{
    return EnvironmentUnitTest::$functionExistsSpy
        ->reveal()
        ->report($function);
}

function set_time_limit(int $seconds): bool
{
    return EnvironmentUnitTest::$setTimeLimitSpy
        ->reveal()
        ->report($seconds);
}
