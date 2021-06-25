<?php

namespace PhpTuf\ComposerStager\Tests\PHPStan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Requires non-domain classes to be marked internal.
 */
class InternalRule extends AbstractRule
{
    public function processNode(Node $node, Scope $scope): array
    {
        $class = $this->getClassReflection($node);

        if ($this->isDomainClass($class) || $this->isThrowable($class)) {
            return [];
        }

        if (!$class->isInternal()) {
            return [RuleErrorBuilder::message('Non-domain class must be marked internal (@internal)')->build()];
        }

        return [];
    }
}
