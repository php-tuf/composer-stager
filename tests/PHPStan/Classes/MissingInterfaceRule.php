<?php

namespace PhpTuf\ComposerStager\Tests\PHPStan\Classes;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PhpTuf\ComposerStager\Tests\PHPStan\AbstractRule;

/**
 * Requires non-utility classes to have a corresponding interface.
 */
class MissingInterfaceRule extends AbstractRule
{
    public function getNodeType(): string
    {
        return Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $class = $this->getClassReflection($node);

        if ($this->isUtilClass($class) ||
            $class->isInterface() ||
            $class->isAbstract() ||
            $this->isThrowable($class)
        ) {
            return [];
        }

        $namespace = $this->getNamespace($class->getName());
        foreach ($class->getInterfaces() as $interface) {
            if ($this->getNamespace($interface->getName()) === $namespace) {
                return [];
            }
        }

        $message = sprintf('Non-utility class must implement an interface in the same namespace, i.e., %s', $namespace);
        return [RuleErrorBuilder::message($message)->build()];
    }
}
