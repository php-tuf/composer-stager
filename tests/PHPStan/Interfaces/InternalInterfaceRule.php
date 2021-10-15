<?php

namespace PhpTuf\ComposerStager\Tests\PHPStan\Interfaces;

use PhpParser\Node;
use PhpParser\Node\Stmt\Interface_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PhpTuf\ComposerStager\Tests\PHPStan\AbstractRule;

/**
 * Forbids interfaces to be marked internal.
 */
class InternalInterfaceRule extends AbstractRule
{
    public function getNodeType(): string
    {
        return Interface_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $class = $this->getClassReflection($node);

        if ($class->isInternal()) {
            return [RuleErrorBuilder::message('Interface cannot be marked internal (@internal)')->build()];
        }

        return [];
    }
}
