<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPStan\Rules\Properties;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PhpTuf\ComposerStager\Tests\PHPStan\Rules\AbstractRule;

/** Requires class properties to be alphabetized within their visibility grouping. */
final class SortedPropertiesRule extends AbstractRule
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

        $current = $previous = [
            'private' => '',
            'protected' => '',
            'public' => '',
        ];

        $errors = [];

        foreach ($class->getNativeReflection()->getProperties() as $property) {
            // Skip inherited properties.
            if ($property->getDeclaringClass()->getName() !== $class->getName()) {
                continue;
            }

            $visibility = $this->getVisibility($property);

            $current[$visibility] = $property->getName();

            if (strcmp($current[$visibility], $previous[$visibility]) < 0) {
                if (array_key_exists($visibility, $errors)) {
                    continue;
                }

                $message = sprintf(
                    '%s properties should be sorted alphabetically by variable. The first wrong one is "$%s".',
                    ucfirst($visibility),
                    $current[$visibility],
                );
                $errors[$visibility] = RuleErrorBuilder::message($message)->build();
            }

            $previous[$visibility] = $current[$visibility];
        }

        return array_values($errors);
    }

    private function getVisibility(ReflectionProperty $property): string
    {
        if ($property->isPublic()) {
            return 'public';
        }

        if ($property->isProtected()) {
            return 'protected';
        }

        if ($property->isPrivate()) {
            return 'private';
        }

        throw new ShouldNotHappenException();
    }
}
