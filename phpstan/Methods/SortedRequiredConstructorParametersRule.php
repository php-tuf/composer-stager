<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPStan\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\RuleErrorBuilder;
use PhpTuf\ComposerStager\PHPStan\AbstractRule;

/** Requires non-optional constructor parameters to be alphabetized. */
final class SortedRequiredConstructorParametersRule extends AbstractRule
{
    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $method = $this->getMethodReflection($scope);

        if ($method->getName() !== '__construct') {
            return [];
        }

        $variant = ParametersAcceptorSelector::selectSingle($method->getVariants());
        $parameters = $variant->getParameters();

        $previous = '';

        foreach ($parameters as $parameter) {
            if ($parameter->isOptional()) {
                return [];
            }

            $current = $parameter->getName();

            if (strcmp($current, $previous) < 0) {
                $message = sprintf(
                    'Non-required constructor parameters should be sorted alphabetically by variable name. The first wrong one is $%s.',
                    $current,
                );

                return [RuleErrorBuilder::message($message)->build()];
            }

            $previous = $current;
        }

        return [];
    }
}
