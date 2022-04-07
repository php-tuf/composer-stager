<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPStan\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\RuleErrorBuilder;
use PhpTuf\ComposerStager\Tests\PHPStan\AbstractRule;

/**
 * Forbids throwing non-PhpTuf exceptions from public methods.
 */
final class ForbiddenThrowsRule extends AbstractRule
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
            $class = $this->reflectionProvider->getClass($exception);

            if ($this->isProjectClass($class)) {
                continue;
            }

            $message = sprintf(
                'Built-in or third party exception "\%s" cannot be thrown from public methods. Catch it and throw the appropriate ComposerStager exception instead',
                $exception
            );
            $errors[] = RuleErrorBuilder::message($message)->build();
        }

        return $errors;
    }
}
