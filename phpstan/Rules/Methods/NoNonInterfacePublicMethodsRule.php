<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPStan\Rules\Methods;

use PhpParser\Node;
use PhpParser\Node\Stmt\Interface_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PhpTuf\ComposerStager\PHPStan\Rules\AbstractRule;
use ReflectionClass;
use Throwable;

/** Forbids public methods that are not on an interface. */
final class NoNonInterfacePublicMethodsRule extends AbstractRule
{
    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof InClassMethodNode);

        $class = $scope->getClassReflection();

        // Ignore interfaces.
        if ($class->isInterface()) {
            return [];
        }

        // Ignore exceptions.
        if ($class->is(Throwable::class)) {
            return[];
        }

        $method = $this->getMethodReflection($scope);
        $methodName = $method->getName();

        if ($methodName === '__construct') {
            return [];
        }

        if (!$method->isPublic()) {
            return [];
        }

        $errors = [];

        $interfaces = $class->getInterfaces();

        foreach ($interfaces as $variant) {
            if (!$variant->hasMethod($methodName)) {
                return [$this->buildErrorMessage(sprintf(
                    "Method %s() should not be public because it's not on any interface its class implements.",
                    $methodName,
                ))];
            }
        }

        return $errors;
    }
}
