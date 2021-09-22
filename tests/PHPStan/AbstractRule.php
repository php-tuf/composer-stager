<?php

namespace PhpTuf\ComposerStager\Tests\PHPStan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
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

    protected function getClassReflection(Node $node): ClassReflection
    {
        if (!isset($node->namespacedName)) {
            throw new ShouldNotHappenException();
        }

        return $this->reflectionProvider->getClass($node->namespacedName);
    }

    protected function getMethodReflection(Scope $scope): MethodReflection
    {
        $methodReflection = $scope->getFunction();

        if (!$methodReflection instanceof MethodReflection) {
            throw new ShouldNotHappenException();
        }

        return $methodReflection;
    }

    protected function isProjectClass(ClassReflection $class): bool
    {
        return $this->isInNamespace($class->getName(), 'PhpTuf\ComposerStager\\');
    }

    protected function isProtectedMethod(MethodReflection $method): bool
    {
        return !$method->isPublic() && !$method->isPrivate();
    }

    protected function isApplicationClass(ClassReflection $class): bool
    {
        return $this->isInNamespace($class->getName(), 'PhpTuf\ComposerStager\Console\\');
    }

    protected function isDomainClass(ClassReflection $class): bool
    {
        return $this->isInNamespace($class->getName(), 'PhpTuf\ComposerStager\Domain\\');
    }

    protected function isUtilClass(ClassReflection $class): bool
    {
        return $this->isInNamespace($class->getName(), 'PhpTuf\ComposerStager\Util\\');
    }

    protected function isThrowable(ClassReflection $class): bool
    {
        return array_key_exists('Throwable', $class->getInterfaces());
    }

    protected function isInNamespace(string $name, string $namespace): bool
    {
        return strpos($name, $namespace) === 0;
    }

    protected function getNamespace(string $name): string
    {
        $nameParts = explode('\\', $name);
        array_pop($nameParts);
        return implode('\\', $nameParts);
    }
}
