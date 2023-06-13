<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPStan\Rules\PhpDoc;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\Analyser\Scope;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableAwareTrait;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Infrastructure\Translation\Value\TranslationParameters;
use PhpTuf\ComposerStager\PHPStan\Rules\AbstractRule;
use ReflectionClass;
use ReflectionMethod;

/** Ensures that the DocBlocks for TranslationParameters creation methods stay in sync. */
final class TranslationParametersPhpDocRule extends AbstractRule
{
    /** The class that covered methods must be in sync with. */
    private const CANONICAL_CLASS = TranslationParameters::class;

    /** The method that covered methods must be in sync with. */
    private const CANONICAL_METHOD = '__construct';

    /** The class/method pairs that must be in sync with the canonical class. */
    private const COVERED_METHODS = [
        TranslatableFactoryInterface::class => 'createTranslationParameters',
        TranslatableAwareTrait::class => 'p',
    ];

    public function getNodeType(): string
    {
        return ClassLike::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof ClassLike);

        $className = (string) $node->namespacedName;

        // Limit to covered methods.
        if (!array_key_exists($className, self::COVERED_METHODS)) {
            return [];
        }

        $methodName = self::COVERED_METHODS[$className];
        $method = $node->getMethod($methodName);

        // Make sure the expected method exists.
        if ($method === null) {
            return [
                $this->buildErrorMessage(sprintf(
                    'Expected method "%s()" is missing. See %s::COVERED_METHODS',
                    $methodName,
                    self::class,
                )),
            ];
        }

        $canonicalClass = new ReflectionClass(self::CANONICAL_CLASS);
        $canonicalMethod = $canonicalClass->getMethod(self::CANONICAL_METHOD);
        assert($canonicalMethod instanceof ReflectionMethod);
        $canonicalDocComment = $canonicalMethod->getDocComment();

        if ($method->getDocComment() === $canonicalDocComment) {
            return [];
        }

        $methodDocComment = $method->getDocComment();
        $methodDocComment = $methodDocComment instanceof Doc
            ? $methodDocComment->getText()
            : '';

        if ($methodDocComment !== $canonicalDocComment) {
            return [
                $this->buildErrorMessage(sprintf(
                    'Docblock must match the one for %s::%s().',
                    self::CANONICAL_CLASS,
                    self::CANONICAL_METHOD,
                )),
            ];
        }

        return [];
    }
}
