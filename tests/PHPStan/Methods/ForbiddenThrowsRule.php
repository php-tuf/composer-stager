<?php

namespace PhpTuf\ComposerStager\Tests\PHPStan\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\RuleErrorBuilder;
use PhpTuf\ComposerStager\Tests\PHPStan\AbstractRule;

/**
 * Forbids throwing non-PhpTuf exceptions from non-application code public methods.
 */
class ForbiddenThrowsRule extends AbstractRule
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

        $class = $scope->getClassReflection();
        if ($this->isApplicationClass($class)) {
            return [];
        }

        $errors = [];
        foreach ($throwType->getReferencedClasses() as $exception) {
            $class = $this->reflectionProvider->getClass($exception);
            if ($this->isProjectClass($class)) {
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
}
