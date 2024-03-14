<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

final class BasicTestHelper
{
    /** If a value is a string, wrap it in an array, i.e., return it as an array of itself. */
    public static function ensureIsArray(array|string $values): array
    {
        if (is_string($values)) {
            return [$values];
        }

        return $values;
    }
}
