<?php

namespace PhpTuf\ComposerStager\Tests\PHPStan\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PhpTuf\ComposerStager\Tests\PHPStan\AbstractRule;

/**
 * Requires utility classes to be non-instantiable.
 */
class UtilityClassInstantiableRule extends AbstractRule
{
    public function processNode(Node $node, Scope $scope): array
    {
        $class = $this->getClassReflection($node);

        if (!$this->isUtilClass($class)) {
            return [];
        }

        $constructor = $class->getConstructor();

        if ($class->isFinalByKeyword() && $constructor->isPrivate()
        ) {
            return [];
        }

        return [RuleErrorBuilder::message('Utility class must be non-instantiable, i.e., final with a private constructor')->build()];
    }
}
