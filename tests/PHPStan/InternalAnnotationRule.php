<?php

namespace PhpTuf\ComposerStager\Tests\PHPStan;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Requires non-domain classes to be marked "@internal".
 */
class InternalAnnotationRule implements Rule
{
    public function getNodeType(): string
    {
        return Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        // Exclude domain classes and exceptions.
        $namespace = $scope->getNamespace();
        $isDomainClass = strpos($namespace, 'PhpTuf\ComposerStager\Domain') === 0;
        $isExceptionClass = strpos($namespace, 'PhpTuf\ComposerStager\Exception') === 0;
        if ($isDomainClass || $isExceptionClass) {
            return [];
        }

        // Get docComment text.
        $docComment = $node->getDocComment();
        if ($docComment instanceof Doc) {
            $docComment = $docComment->getText();
        }

        // Search for annotation.
        if (!strpos($docComment, PHP_EOL . ' * @internal' . PHP_EOL)) {
            return [RuleErrorBuilder::message('Non-domain class is not marked @internal.')->build()];
        }

        return [];
    }
}
