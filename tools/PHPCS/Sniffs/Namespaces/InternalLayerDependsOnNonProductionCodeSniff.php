<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PhpTuf\ComposerStager\Helper\NamespaceHelper;

/** Finds Internal code that depends on non-production code, e.g., tests or vendor code. */
final class InternalLayerDependsOnNonProductionCodeSniff implements Sniff
{
    private const CODE_DEPENDS_ON_NON_API_LAYER = 'DependsOnNonProductionCode';

    public function register(): array
    {
        return [T_USE];
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        // Not an Internal layer source file. (This apparently can't be controlled with
        // exclusions in phpcs.xml due to the way the custom sniffs are included.)
        if (!$this->isInternalFile($phpcsFile)) {
            return;
        }

        $namespace = NamespaceHelper::getNamespace($phpcsFile, $stackPtr);

        // Not a scope-level namespace, e.g., a class or interface.
        // (It's probably an anonymous function declaration.)
        if (!$this->isScopeLevelNamespace($phpcsFile, $stackPtr)) {
            return;
        }

        // The namespace is non-production code.
        if (!$this->isNonProductionCode($namespace)) {
            return;
        }

        $phpcsFile->addError(
            sprintf(
                'Internal-layer class %s must not depend on non-production class %s.',
                $this->getClass($phpcsFile),
                $namespace,
            ),
            $stackPtr,
            self::CODE_DEPENDS_ON_NON_API_LAYER,
        );
    }

    private function isInternalFile(File $phpcsFile): bool
    {
        $srcDir = dirname(__DIR__, 4) . '/src/Internal';

        return str_starts_with($phpcsFile->getFilename(), $srcDir);
    }

    private function isScopeLevelNamespace(File $phpcsFile, int $stackPtr): bool
    {
        $scopePtr = $phpcsFile->findNext(Tokens::$ooScopeTokens, $stackPtr);

        return $scopePtr !== false;
    }

    private function isNonProductionCode(string $namespace): bool
    {
        // Namespace is in Composer Stager itself.
        if (!str_starts_with($namespace, 'PhpTuf\\ComposerStager')) {
            return false;
        }

        // Namespace is not in either production layer.
        return
            !str_starts_with($namespace, 'PhpTuf\\ComposerStager\\API')
            && !str_starts_with($namespace, 'PhpTuf\\ComposerStager\\Internal')
        ;
    }

    private function getClass(File $phpcsFile): string
    {
        return $this->getClassNamespace($phpcsFile) . '\\' . $this->getClassName($phpcsFile);
    }

    private function getClassNamespace(File $phpcsFile): string
    {
        $namespaceTokenPtr = $phpcsFile->findNext(T_NAMESPACE, 0);
        $namespacePtr = $phpcsFile->findNext(T_STRING, $namespaceTokenPtr);

        return NamespaceHelper::getNamespace($phpcsFile, $namespacePtr);
    }

    private function getClassName(File $phpcsFile): mixed
    {
        $tokens = $phpcsFile->getTokens();
        $scopeTokenPtr = $phpcsFile->findNext(Tokens::$ooScopeTokens, 0);
        $classNamePtr = $phpcsFile->findNext(T_STRING, $scopeTokenPtr);

        return $tokens[$classNamePtr]['content'];
    }
}
