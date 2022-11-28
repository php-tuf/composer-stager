<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PhpTuf\ComposerStager\Tests\PHPStan\Rules\AbstractRule;

/** Requires "@property" data types to put ObjectProphecy last. */
final class PropertyDataTypePutsObjectProphecyLastRule extends AbstractRule
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
            $lineParts = explode(' ', $line);

            foreach ($lineParts as $linePart) {
                // Not a (union) data type.
                if (strpos($linePart, '|') === false) {
                    continue;
                }

                $dataTypes = explode('|', $linePart);

                // Remove the last data type.
                array_pop($dataTypes);

                foreach ($dataTypes as $dataType) {
                    $fqnParts = explode('\\', $dataType);
                    $className = end($fqnParts);

                    if ($className !== 'ObjectProphecy') {
                        continue;
                    }

                    $message = sprintf(
                        '"ObjectProphecy" should be last in the list of @property data types in %s.',
                        $linePart . ' ' . end($lineParts),
                    );
                    $errors[] = RuleErrorBuilder::message($message)->build();
                }
            }
        }

        return $errors;
    }
}
