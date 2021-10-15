<?php

namespace PhpTuf\ComposerStager\Tests\PHPStan\Classes;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PhpTuf\ComposerStager\Tests\PHPStan\AbstractRule;

/**
 * Requires non-domain classes to be marked internal.
 */
class MissingInternalAnnotationRule extends AbstractRule
{
    public function getNodeType(): string
    {
        return Class_::class;
    }

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
