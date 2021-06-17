<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Exception;

use PhpTuf\ComposerStager\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Tests\Unit\TestCase;

class ExceptionsTest extends TestCase
{
    /**
     * It may seem silly to test that exception classes implement an interface,
     * but it's an easy thing to forget.
     *
     * @coversNothing
     *
     * @dataProvider providerExceptionsImplementInterface
     */
    public function testExceptionsImplementInterface($class): void
    {
        self::assertTrue(
            is_a($class, ExceptionInterface::class, true),
            sprintf('%s implements %s', $class, ExceptionInterface::class)
        );
    }

    public function providerExceptionsImplementInterface(): array
    {
        // Get the Composer autoloader class map.
        $classMap = require __DIR__ . '/../../../vendor/composer/autoload_classmap.php';
        $exceptions = [];
        foreach (array_keys($classMap) as $class) {
            // Limit to classes in the project exceptions namespace.
            if (strpos($class, 'PhpTuf\ComposerStager\Exception') !== 0) {
                continue;
            }
            $exceptions[][] = $class;
        }
        return $exceptions;
    }
}
