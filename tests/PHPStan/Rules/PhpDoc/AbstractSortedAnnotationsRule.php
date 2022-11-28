<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/** Provides a base class for alphabetized annotation rules. */
abstract class AbstractSortedAnnotationsRule implements Rule
{
    abstract protected function targetTag(): string;

    final public function getNodeType(): string
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

        $previous = '';

        foreach (explode(PHP_EOL, $docComment->getText()) as $line) {
            $lineParts = explode(' ' . $this->targetTag() . ' ', $line);

            // No matching annotation found.
            if (count($lineParts) === 1) {
                continue;
            }

            $current = trim($lineParts[1]);

            if (strcasecmp($current, $previous) < 0) {
                $message = sprintf(
                    '%s annotations should be sorted alphabetically. The first wrong one is %s.',
                    $this->targetTag(),
                    $current,
                );

                return [RuleErrorBuilder::message($message)->build()];
            }

            $previous = $current;
        }

        return [];
    }
}
