<?php

namespace PhpTuf\ComposerStager\Tests\PHPStan\Interfaces;

use PhpParser\Node;
use PhpParser\Node\Stmt\Interface_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PhpTuf\ComposerStager\Tests\PHPStan\AbstractRule;

class UselessInterfaceRule extends AbstractRule
{
    public function getNodeType(): string
    {
        return Interface_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $interface = $this->getClassReflection($node);
        $methods = $interface->getNativeReflection()->getMethods();

        if (count($methods) > 0) {
            return [];
        }

        $message = 'Interface is useless: it has no methods';
        return [RuleErrorBuilder::message($message)->build()];
    }
}
