<?php

namespace PhpTuf\ComposerStager\Tests\PHPStan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Requires concrete classes to be final.
 *
 * @see https://ocramius.github.io/blog/when-to-declare-classes-final/
 */
class FinalRule extends AbstractRule
{
    public function processNode(Node $node, Scope $scope): array
    {
        $class = $this->getClassReflection($node);

        if ($class->isInterface() || $class->isAbstract() || $this->isThrowable($class)) {
            return [];
        }

        if (!$class->isFinalByKeyword()) {
            return [RuleErrorBuilder::message('Concrete class must be final')->build()];
        }

        return [];
    }
}
