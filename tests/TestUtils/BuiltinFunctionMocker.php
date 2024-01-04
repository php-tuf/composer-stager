<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

/**
 * Provides the ability to mock built-in PHP functions.
 *
 * @see tests/TestUtils/builtin_function_mocks.inc
 */
final class BuiltinFunctionMocker
{
    /** @var array<string,\Prophecy\Prophecy\ObjectProphecy|\PhpTuf\ComposerStager\Tests\TestUtils\TestSpyInterface> */
    public static array $spies = [];

    /**
     * Mocks the given functions.
     *
     * Declare new functions in the appropriate namespace in {@see tests/TestUtils/builtin_function_mocks.inc}.
     *
     * Example:
     * ```php
     * BuiltinFunctionMocker::mock([
     *     'chmod' => $this->prophesize(TestSpyInterface::class),
     *     'md5' => $this->prophesize(TestSpyInterface::class),
     * ]);
     * ```
     *
     * Remember to tag your test method `@runInSeparateProcess` to avoid test pollution.
     *
     * @param array<string,\Prophecy\Prophecy\ObjectProphecy|\PhpTuf\ComposerStager\Tests\TestUtils\TestSpyInterface> $spies
     *   An array of spies (prophecies) keyed by the names of built-in PHP functions to mock. See example above.
     */
    public static function mock(array $spies): void
    {
        foreach ($spies as $functionName => $prophecy) {
            self::$spies[$functionName] = $prophecy;
        }

        require_once __DIR__ . '/builtin_function_mocks.inc';
    }

    /** Determines whether to mock the given function. */
    public static function shouldMock(string $functionName): bool
    {
        return array_key_exists($functionName, self::$spies);
    }
}
