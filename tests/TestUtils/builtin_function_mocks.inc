<?php declare(strict_types=1);

/** @file Built-in function mocks for unit tests. {@see \PhpTuf\ComposerStager\Tests\TestUtils\BuiltinFunctionMocker} */

namespace PhpTuf\ComposerStager\Internal\Environment\Service {

    use PhpTuf\ComposerStager\Tests\TestUtils\BuiltinFunctionMocker;

    if (BuiltinFunctionMocker::shouldMock('function_exists')) {
        function function_exists(string $function): bool
        {
            return BuiltinFunctionMocker::$spies['function_exists']
                ->reveal()
                ->report($function);
        }
    }

    if (BuiltinFunctionMocker::shouldMock('set_time_limit')) {
        function set_time_limit(int $seconds): bool
        {
            return BuiltinFunctionMocker::$spies['set_time_limit']
                ->reveal()
                ->report($seconds);
        }
    }
}

namespace PhpTuf\ComposerStager\Internal\Filesystem\Service {

    use PhpTuf\ComposerStager\Tests\TestUtils\BuiltinFunctionMocker;

    if (BuiltinFunctionMocker::shouldMock('chmod')) {
        function chmod(string $filename, int $permissions): bool
        {
            return BuiltinFunctionMocker::$spies['chmod']
                ->reveal()
                ->report($filename, $permissions);
        }
    }

    if (BuiltinFunctionMocker::shouldMock('fileperms')) {
        function fileperms(string $filename): int|false
        {
            return BuiltinFunctionMocker::$spies['fileperms']
                ->reveal()
                ->report($filename);
        }
    }
}

namespace PhpTuf\ComposerStager\Internal\Path\Value {

    use PhpTuf\ComposerStager\Tests\TestUtils\BuiltinFunctionMocker;

    if (BuiltinFunctionMocker::shouldMock('getcwd')) {
        function getcwd(): string|false
        {
            return BuiltinFunctionMocker::$spies['getcwd']
                ->reveal()
                ->report();
        }
    }

    if (BuiltinFunctionMocker::shouldMock('md5')) {
        function md5(): string
        {
            return BuiltinFunctionMocker::$spies['md5']
                ->reveal()
                ->report();
        }
    }
}
