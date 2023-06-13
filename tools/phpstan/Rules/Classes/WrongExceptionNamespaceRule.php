<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPStan\Rules\Classes;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PhpTuf\ComposerStager\PHPStan\Rules\AbstractRule;
use Throwable;

/** Requires exceptions to be in the correct namespace. */
final class WrongExceptionNamespaceRule extends AbstractRule
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

        $reflection = $class->getNativeReflection();
        $namespace = $reflection->getNamespaceName();

        if (!$this->isInNamespace($namespace, 'PhpTuf\\ComposerStager\\Domain\\Exception\\')) {
            return [
                $this->buildErrorMessage(
                    'Exception must be in the PhpTuf\\ComposerStager\\Domain\\Exception namespace',
                ),
            ];
        }

        return [];
    }
}
