<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPStan\Rules\Classes;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PhpTuf\ComposerStager\PHPStan\Rules\AbstractRule;
use Throwable;

/** Requires concrete classes to implement an interface. */
final class MissingInterfaceRule extends AbstractRule
{
    public function getNodeType(): string
    {
        return Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $class = $this->getClassReflection($node);

        if ($class === null) {
            return [];
        }

        if ($class->isInterface() || $class->isAbstract() || $class->is(Throwable::class)) {
            return [];
        }

        if ($class->getInterfaces() !== []) {
            return [];
        }

        $message = 'Concrete class must implement an interface';

        return [$this->buildErrorMessage($message)];
    }
}
