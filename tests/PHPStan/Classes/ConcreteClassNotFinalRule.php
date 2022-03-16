<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPStan\Classes;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PhpTuf\ComposerStager\Tests\PHPStan\AbstractRule;

/**
 * Requires concrete classes to be final.
 *
 * @see https://ocramius.github.io/blog/when-to-declare-classes-final/
 */
class ConcreteClassNotFinalRule extends AbstractRule
{
    public function getNodeType(): string
    {
        return Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $class = $this->getClassReflection($node);

        if ($class->isInterface() ||
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
