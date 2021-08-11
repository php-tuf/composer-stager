<?php

namespace PhpTuf\ComposerStager\Tests\PHPStan\Classes;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;

/**
 * Provides a base class for PHPStan class rules.
 */
abstract class AbstractRule implements Rule
{
    /**
     * @var \PHPStan\Reflection\ReflectionProvider
     */
    protected $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getNodeType(): string
    {
        return Class_::class;
    }

    protected function getClassReflection(Node $node): ClassReflection
    {
        if (!isset($node->namespacedName)) {
            throw new ShouldNotHappenException();
        }

        return $this->reflectionProvider->getClass($node->namespacedName);
    }

    protected function isApplicationClass(ClassReflection $class): bool
    {
        return strpos($class->getName(), 'PhpTuf\ComposerStager\Console') === 0;
    }

    protected function isDomainClass(ClassReflection $class): bool
    {
        return strpos($class->getName(), 'PhpTuf\ComposerStager\Domain') === 0;
    }

    protected function isThrowable(ClassReflection $class): bool
    {
        return array_key_exists('Throwable', $class->getInterfaces());
    }
}
