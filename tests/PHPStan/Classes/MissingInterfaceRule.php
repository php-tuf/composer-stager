<?php

namespace PhpTuf\ComposerStager\Tests\PHPStan\Classes;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PhpTuf\ComposerStager\Tests\PHPStan\AbstractRule;

/**
 * Requires non-utility classes to implement an interface.
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

        if ($class->getInterfaces() !== []) {
            return [];
        }

        $message = sprintf('Non-utility classes to implement an interface.');
        return [RuleErrorBuilder::message($message)->build()];
    }
}
