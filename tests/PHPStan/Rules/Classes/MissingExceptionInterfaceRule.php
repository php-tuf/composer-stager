<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPStan\Rules\Classes;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\PHPStan\Rules\AbstractRule;
use Throwable;

/** Requires exceptions to implement ExceptionInterface. */
final class MissingExceptionInterfaceRule extends AbstractRule
{
    public function getNodeType(): string
    {
        return Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $class = $this->getClassReflection($node);

        if (!$class instanceof ClassReflection) {
            return [];
        }

        if (!$class->is(Throwable::class)) {
            return[];
        }

        if (!$class->is(ExceptionInterface::class)) {
            return [
                $this->buildErrorMessage(sprintf(
                    'Exception must implement %s',
                    ExceptionInterface::class,
                )),
            ];
        }

        return [];
    }
}
