<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPStan\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\RuleErrorBuilder;
use PhpTuf\ComposerStager\PHPStan\AbstractRule;
use ReflectionClass;

/** Forbids using concrete classes in type hints when an interface is available. */
final class ForbiddenConcreteTypeHintRule extends AbstractRule
{
    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $method = $this->getMethodReflection($scope);

        if ($method->getName() !== '__construct') {
            return [];
        }

        $errors = [];

        $variants = $method->getVariants();

        foreach ($variants as $variant) {
            foreach ($variant->getParameters() as $parameter) {
                $referencedClasses = $parameter->getType()->getReferencedClasses();

                foreach ($referencedClasses as $class) {
                    $class = new ReflectionClass($class);

                    // The type hint is an interface.
                    if ($class->isInterface()) {
                        continue;
                    }

                    $interfaces = $class->getInterfaceNames();

                    // The type hint does not implement any interfaces it could use.
                    if ($interfaces === []) {
                        continue;
                    }

                    // The type hint is a concrete class that implements interfaces it could use.
                    $message = sprintf(
                        'Constructor parameter "$%s" cannot type hint to concrete class "%s". Use an interface it implements instead',
                        $parameter->getName(),
                        $class->getName(),
                    );
                    $errors[] = RuleErrorBuilder::message($message)->build();
                }
            }
        }

        return $errors;
    }
}
