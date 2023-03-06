<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PhpTuf\ComposerStager\PHPStan\Rules\AbstractRule;

/** Ensures that coverage annotations have no trailing parentheses. */
final class CoverageAnnotationHasNoParenthesesRule extends AbstractRule
{
    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof ClassLike
            && !$node instanceof FunctionLike
            && !$node instanceof Property
            && !$node instanceof ClassConst
        ) {
            return [];
        }

        $docComment = $node->getDocComment();

        if ($docComment === null) {
            return [];
        }

        $errors = [];

        foreach (explode(PHP_EOL, $docComment->getText()) as $line) {
            foreach (['@covers', '@uses'] as $tag) {
                $lineParts = explode(' ' . $tag . ' ', $line);

                // No matching annotation found.
                if (count($lineParts) === 1) {
                    continue;
                }

                $value = end($lineParts);

                // The function name does not end with parentheses.
                if (substr(trim($value), -2) !== '()') {
                    continue;
                }

                $message = sprintf('%s function name %s must not end with parentheses.', $tag, $value);
                $errors[] = RuleErrorBuilder::message($message)->build();
            }
        }

        return $errors;
    }
}
