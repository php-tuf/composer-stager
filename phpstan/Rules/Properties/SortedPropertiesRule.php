<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPStan\Rules\Properties;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty;
use PHPStan\ShouldNotHappenException;
use PhpTuf\ComposerStager\PHPStan\Rules\AbstractRule;

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

        $current = [
            'private' => '',
            'protected' => '',
            'public' => '',
        ];
        $previous = $current;

        $errors = [];

        foreach ($class->getNativeReflection()->getProperties() as $property) {
            // Skip inherited properties. This doesn't catch properties
            // declared on an interface, such as \Prophecy\PhpUnit\ProphecyTrait.
            // For now, exclude errors from such properties in phpstan.neon.dist.
            if ($property->getDeclaringClass()->getName() !== $class->getName()) {
                continue;
            }

            // Skip promoted properties.
            if ($property->isPromoted()) {
                continue;
            }

            $visibility = $this->getVisibility($property);

            $current[$visibility] = $property->getName();

            if (strcmp($current[$visibility], $previous[$visibility]) < 0) {
                if (array_key_exists($visibility, $errors)) {
                    continue;
                }

                $errors[$visibility] = $this->buildErrorMessage(sprintf(
                    '%s properties should be sorted alphabetically by variable. The first wrong one is "$%s".',
                    ucfirst($visibility),
                    $current[$visibility],
                ));
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
