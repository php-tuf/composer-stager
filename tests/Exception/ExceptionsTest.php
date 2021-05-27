<?php

namespace PhpTuf\ComposerStager\Tests\Exception;

use PhpTuf\ComposerStager\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Tests\TestCase;
use ReflectionClass;

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
        $reflection = new ReflectionClass($class);
        self::assertTrue(
            $reflection->implementsInterface(ExceptionInterface::class),
            sprintf('%s implements %s', $class, ExceptionInterface::class)
        );
    }

    public function providerExceptionsImplementInterface(): array
    {
        // Get the Composer autoloader class map.
        $classMap = require __DIR__ . '/../../vendor/composer/autoload_classmap.php';
        $exceptions = [];
        foreach (array_keys($classMap) as $class) {
            // Limit to classes in the project exceptions namespace.
            if (strpos($class, 'PhpTuf\ComposerStager\Exception') !== 0) {
                continue;
            }
            // Filter out interfaces.
            $reflection = new ReflectionClass($class);
            if ($reflection->isInterface()) {
                continue;
            }
            // Return FQNs.
            $exceptions[][] = $reflection->getName();
        }
        return $exceptions;
    }
}
