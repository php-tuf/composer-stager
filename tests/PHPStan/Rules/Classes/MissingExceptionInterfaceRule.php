<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPStan\Rules\Classes;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PhpTuf\ComposerStager\Domain\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Tests\PHPStan\Rules\AbstractRule;
use Throwable;

/** Requires throwable classes to implement ExceptionInterface. */
final class MissingExceptionInterfaceRule extends AbstractRule
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

        if (!$class->is(ExceptionInterface::class)) {
            $message = sprintf('Throwable class must implement %s', ExceptionInterface::class);

            return [RuleErrorBuilder::message($message)->build()];
        }

        return [];
    }
}
