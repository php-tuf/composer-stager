<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

use PhpTuf\ComposerStager\Tests\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/** Provides the ability to mock built-in PHP functions. */
final class BuiltinFunctionMocker
{
    /** @var array string<ObjectProphecy|TestSpyInterface> */
    public static array $spies = [];

    /**
     * Mocks the given functions.
     *
     * Declare new functions in the appropriate namespace in {@see tests/TestUtils/builtin_function_mocks.inc}.
     *
     * @param array $functionNames
     *   An array of names of built-in PHP functions to mock, e.g., `['chmod', 'md5']`.
     */
    public static function mock(array $functionNames): void
    {
        $prophet = new class extends TestCase {
            use ProphecyTrait;

            public function getProphecy(): ObjectProphecy
            {
                return $this->prophesize(TestSpyInterface::class);
            }
        };

        foreach ($functionNames as $functionName) {
            self::$spies[$functionName] = $prophet->getProphecy();
        }

        require __DIR__ . '/builtin_function_mocks.inc';
    }

    /** Determines whether to mock the given function. */
    public static function shouldMock(string $functionName): bool
    {
        return array_key_exists($functionName, self::$spies);
    }
}
