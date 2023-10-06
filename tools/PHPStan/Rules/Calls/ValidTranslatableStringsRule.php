<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPStan\Rules\Calls;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableAwareTrait;

/**
 * Ensures that TranslatableAwareTrait::t() is only called with literal string messages.
 *
 * @see https://github.com/php-tuf/composer-stager/issues/123 Add support for string translation for names, descriptions, and messages
 */
final class ValidTranslatableStringsRule implements Rule
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof MethodCall);

        if (!$this->methodCallIsInScope($node, $scope)) {
            return [];
        }

        if ($this->messageArgIsAString($node)) {
            return [];
        }

        // The only exception to the rule is using the error message from a caught
        // exception, which, of course, is impossible to extract translatable strings from.
        if ($this->messageArgIsACaughtExceptionMessage($node)) {
            return [];
        }

        return [
            sprintf(
                'The "$message" argument of the "t()" method can only be a literal string--no variables, concatenation, constants, or method calls--i.e., "Scalar_String". Got "%s".',
                $this->getArgType($node),
            ),
        ];
    }

    /** Determines whether the method call is in scope for analysis. */
    private function methodCallIsInScope(MethodCall $node, Scope $scope): bool
    {
        if (!$this->isInSrcDir($scope)) {
            return false;
        }

        if (!$this->usesTranslatableAwareTrait($scope)) {
            return false;
        }

        if (!$this->isTMethod($node)) {
            return false;
        }

        // Treat a missing "$message" argument as out of scope. Other rules will catch that.
        if ($this->isMissingMessageArgument($node)) {
            return false;
        }

        return true;
    }

    /** Determines whether the class is in the "src" directory. */
    private function isInSrcDir(Scope $scope): bool
    {
        $filename = $scope->getFile();
        $srcDir = dirname(__DIR__, 4) . '/src/';

        return str_starts_with($filename, $srcDir);
    }

    /** Determines whether the class uses "TranslatableAwareTrait". */
    private function usesTranslatableAwareTrait(Scope $scope): bool
    {
        $class = $scope->getClassReflection();
        assert($class instanceof ClassReflection);

        return $class->hasTraitUse(TranslatableAwareTrait::class);
    }

    /** Determines whether the method call is the ::t() method. */
    private function isTMethod(MethodCall $node): bool
    {
        $methodName = (string) $node->name;

        return $methodName === 't';
    }

    /** Determines whether the method call is missing the "$message" argument. */
    private function isMissingMessageArgument(MethodCall $node): bool
    {
        $arguments = $node->getArgs();

        return $arguments === [];
    }

    /** Determines whether the method call is valid. */
    private function messageArgIsAString(MethodCall $node): bool
    {
        // For compatibility with Drupal's {@see https://www.drupal.org/project/potx
        // Translation Template Extractor}, ONLY literal strings are allowed.
        // @see https://git.drupalcode.org/project/potx/-/blob/4685dd0e85989987953ba291ea804e7b0a9a27fc/potx.inc#L906
        return $this->getArgType($node) === 'Scalar_String';
    }

    /**
     * Determines whether the method call is a caught exception message.
     *
     * Like this:
     * ```
     * } catch (Throwable $e) {
     *     throw new Exception($this->t($e->getMessage()));
     * }
     * ```
     */
    private function messageArgIsACaughtExceptionMessage(MethodCall $node): bool
    {
        // This code may be a little clumsy, but it essentially works and minimizes false
        // positives. It basically just checks that the method call is inside a "throw"
        // statement. It could be improved by further verifying that the "$message" argument
        // is specifically a call to `::getMessage()` on a caught exception variable. Until
        // then, ANY non-string will pass this check.
        while ($node = $node->getAttribute('parent')) {
            if ($node instanceof Node\Stmt\Throw_) {
                return true;
            }
        }

        return false;
    }

    /** Gets the type of the method call argument. */
    private function getArgType(MethodCall $node): string
    {
        $arguments = $node->getArgs();

        // Get the first argument.
        $argument = reset($arguments);
        assert($argument instanceof Arg);

        return $argument->value->getType();
    }
}
