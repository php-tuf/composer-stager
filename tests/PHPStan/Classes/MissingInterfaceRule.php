<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPStan\Classes;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PhpTuf\ComposerStager\Tests\PHPStan\AbstractRule;

/**
 * Requires non-factory classes to implement an interface.
 */
final class MissingInterfaceRule extends AbstractRule
{
    public function getNodeType(): string
    {
        return Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $class = $this->getClassReflection($node);

        if ($this->isFactoryClass($class) ||
            $class->isInterface() ||
            $class->isAbstract() ||
            $this->isThrowable($class)
        ) {
            return [];
        }

        if ($class->getInterfaces() !== []) {
            return [];
        }

        $message = 'Non-factory class must implement an interface';
        return [RuleErrorBuilder::message($message)->build()];
    }
}
