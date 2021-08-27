<?php

namespace PhpTuf\ComposerStager\Tests\PHPStan\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;

/**
 * Forbids throwing non-PhpTuf exceptions from non-application code public methods.
 */
class ForbiddenThrowsRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $method = $this->getMethodReflection($scope);

        if (!$method->isPublic()) {
            return [];
        }

        $throwType = $method->getThrowType();

        if ($throwType === null) {
            return[];
        }

        $errors = [];
        foreach ($throwType->getReferencedClasses() as $exception) {
            if ($this->isApplicationCode($scope)) {
                continue;
            }

            if ($this->isComposerStagerException($exception)) {
                continue;
            }

            $message = sprintf(
                'Built-in or third party exception "\%s" cannot be thrown from public methods outside the application layer. Catch it and throw the appropriate ComposerStager exception instead.',
                $exception
            );
            $errors[] = RuleErrorBuilder::message($message)->build();
        }

        return $errors;
    }

    private function getMethodReflection(Scope $scope): MethodReflection
    {
        $methodReflection = $scope->getFunction();

        if (!$methodReflection instanceof MethodReflection) {
            throw new ShouldNotHappenException();
        }

        return $methodReflection;
    }

    private function isApplicationCode(Scope $scope): bool
    {
        $classReflection = $scope->getClassReflection();
        return strpos($classReflection->getName(), 'PhpTuf\ComposerStager\Console\\') === 0;
    }

    private function isComposerStagerException(string $exception): bool
    {
        return strpos($exception, 'PhpTuf\ComposerStager\Exception') === 0;
    }
}
