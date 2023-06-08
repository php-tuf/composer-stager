<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPStan\Rules\Calls;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Type\ObjectType;
use PhpTuf\ComposerStager\Domain\Factory\Translation\TranslatableAwareTrait;
use PhpTuf\ComposerStager\Infrastructure\Factory\Translation\TranslatableFactory;

/**
 * Forbids calling TranslatableFactory::create() directly.
 *
 * Inspired by https://github.com/BrandEmbassy/phpstan-forbidden-method-calls-rule. Thanks!
 */
final class NoCallingTranslatableFactoryDirectlyRule implements Rule
{
    private const FORBIDDEN_CALL_CLASS = TranslatableFactory::class;
    private const FORBIDDEN_CALL_METHODS = ['createTranslatableMessage'];

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof MethodCall);

        $method = (string) $node->name;

        // Target the method.
        if (!in_array($method, self::FORBIDDEN_CALL_METHODS, true)) {
            return [];
        }

        $class = new ObjectType(self::FORBIDDEN_CALL_CLASS);

        $type = $scope->getType($node->var);

        // Target the object.
        if (!$class->isSuperTypeOf($type)->yes()) {
            return [];
        }

        return [
            sprintf(
                'Cannot call %s::%s() directly. Use %s::t() instead.',
                self::FORBIDDEN_CALL_CLASS,
                $method,
                TranslatableAwareTrait::class,
            ),
        ];
    }
}
