<?php

namespace PhpTuf\ComposerStager\Tests\PHPStan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Requires non-application classes have a corresponding interface.
 */
class InterfaceRule extends AbstractRule
{
    public function processNode(Node $node, Scope $scope): array
    {
        $class = $this->getClassReflection($node);

        if ($this->isApplicationClass($class) || $class->isInterface() || $class->isAbstract() || $this->isThrowable($class)) {
            return [];
        }

        $expectedInterface = $class->getName() . 'Interface';
        if (!array_key_exists($expectedInterface, $class->getInterfaces())) {
            $message = sprintf('Non-application class must implement a corresponding interface, i.e., %s', $expectedInterface);
            return [RuleErrorBuilder::message($message)->build()];
        }

        return [];
    }
}
