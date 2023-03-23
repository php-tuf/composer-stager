<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Interface_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PhpTuf\ComposerStager\PHPStan\Rules\AbstractRule;
use Throwable;

/** Enforces "@api" and "@internal" class annotation rules. */
final class APIAnnotationRule extends AbstractRule
{
    private const ABSTRACT_CLASS = 'Abstract class';
    private const EXCEPTION = 'Exception';
    private const FACTORY = 'Factory';
    private const CONCRETE_CLASS = 'Concrete class';
    private const INTERFACE = 'Interface';
    private const TEST_CLASS = 'Test class';

    private const PUBLIC_API = [
        self::INTERFACE,
        self::ABSTRACT_CLASS,
        self::FACTORY,
        self::EXCEPTION,
    ];

    public function getNodeType(): string
    {
        return ClassLike::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        // Only check classes and interfaces.
        if (!$node instanceof Class_ && !$node instanceof Interface_) {
            return [];
        }

        $class = $this->getClassReflection($node);

        if ($class === null) {
            return [];
        }

        $type = $this->getType($class);

        // Ignore tests.
        if ($type === self::TEST_CLASS) {
            return [];
        }

        // Define the public API surface.
        // Require classes in the public API to be annotated with @api.
        if (in_array($type, self::PUBLIC_API, true)) {
            return $this->getAnnotationErrors($node, $type, '@api', '@internal');
        }

        // Require everything else to be annotated with @internal.
        return $this->getAnnotationErrors($node, $type, '@internal', '@api');
    }

    private function getAnnotationErrors(
        Class_|Interface_ $node,
        string $type,
        string $requiredTag,
        string $forbiddenTag,
    ): array {
        $errors = [];

        // Missing required annotation.
        if (!$this->hasAnnotation($node, $requiredTag)) {
            $errors[] = $this->buildErrorMessage(sprintf(
                '%s must be marked "%s"',
                $type,
                $requiredTag,
            ));
        }

        // Has forbidden annotation.
        if ($this->hasAnnotation($node, $forbiddenTag)) {
            $errors[] = $this->buildErrorMessage(sprintf(
                '%s cannot be marked "%s"',
                $type,
                $forbiddenTag,
            ));
        }

        return $errors;
    }

    private function getType(ClassReflection $class): string
    {
        $reflection = $class->getNativeReflection();
        $namespace = $reflection->getNamespaceName();

        if ($this->isInNamespace($namespace, 'PhpTuf\\ComposerStager\\Tests\\')) {
            return self::TEST_CLASS;
        }

        if ($reflection->isInterface()) {
            return self::INTERFACE;
        }

        if ($reflection->isAbstract()) {
            return self::ABSTRACT_CLASS;
        }

        if ($class->is(Throwable::class)) {
            return self::EXCEPTION;
        }

        if ($this->isFactoryClass($class)) {
            return self::FACTORY;
        }

        return self::CONCRETE_CLASS;
    }

    private function hasAnnotation(Class_|Interface_ $node, string $tag): bool
    {
        $docComment = $node->getDocComment();

        if ($docComment === null) {
            return false;
        }

        return str_contains($docComment->getText(), $tag);
    }
}
