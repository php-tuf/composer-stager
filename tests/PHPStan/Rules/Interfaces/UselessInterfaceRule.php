<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPStan\Rules\Interfaces;

use PhpParser\Node;
use PhpParser\Node\Stmt\Interface_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PhpTuf\ComposerStager\Tests\PHPStan\Rules\AbstractRule;

/** Forbids empty interfaces, i.e., without methods or constants. */
final class UselessInterfaceRule extends AbstractRule
{
    public function getNodeType(): string
    {
        return Interface_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $interface = $this->getClassReflection($node);
        $reflection = $interface->getNativeReflection();
        $methods = $reflection->getMethods();
        $constants = $reflection->getReflectionConstants();

        if (count($methods) > 0 || count($constants) > 0) {
            return [];
        }

        $message = 'Interface is useless: it has no methods or constants';

        return [RuleErrorBuilder::message($message)->build()];
    }
}
