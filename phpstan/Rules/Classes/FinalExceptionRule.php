<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPStan\Rules\Classes;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PhpTuf\ComposerStager\PHPStan\Rules\AbstractRule;
use Throwable;

/** Forbids exceptions from being final. */
final class FinalExceptionRule extends AbstractRule
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

        if (!$class->is(Throwable::class)) {
            return[];
        }

        if ($class->isFinal()) {
            return [$this->buildErrorMessage('Exception cannot be final')];
        }

        return [];
    }
}
