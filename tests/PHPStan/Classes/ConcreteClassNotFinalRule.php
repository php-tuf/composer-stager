<?php

namespace PhpTuf\ComposerStager\Tests\PHPStan\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PhpTuf\ComposerStager\Tests\PHPStan\Classes\AbstractRule;

/**
 * Requires concrete non-application classes to be final.
 *
 * @see https://ocramius.github.io/blog/when-to-declare-classes-final/
 */
class ConcreteClassNotFinalRule extends AbstractRule
{
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

        if (!$class->isFinalByKeyword()) {
            return [RuleErrorBuilder::message('Concrete class must be final')->build()];
        }

        return [];
    }
}
