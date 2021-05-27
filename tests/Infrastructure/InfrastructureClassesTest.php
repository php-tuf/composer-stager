<?php

namespace PhpTuf\ComposerStager\Tests\Infrastructure;

use PhpTuf\ComposerStager\Tests\TestCase;
use ReflectionClass;

class InfrastructureClassesTest extends TestCase
{
    /**
     * @coversNothing
     *
     * @dataProvider providerClassesAnnotatedInternal
     */
    public function testClassesAnnotatedInternal($class): void
    {
        $reflection = new ReflectionClass($class);
        self::assertStringContainsString(
            '* @internal',
            $reflection->getDocComment(),
            "{$class} has @internal annotation."
        );
    }

    public function providerClassesAnnotatedInternal(): array
    {
        // Get the Composer autoloader class map.
        $classMap = require __DIR__ . '/../../vendor/composer/autoload_classmap.php';
        $exceptions = [];
        foreach (array_keys($classMap) as $class) {
            // Limit to classes in the project infrastructure namespace.
            if (strpos($class, 'PhpTuf\ComposerStager\Infrastructure\\') !== 0) {
                continue;
            }
            // Return FQNs.
            $exceptions[][] = $class;
        }
        return $exceptions;
    }
}
