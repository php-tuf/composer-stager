<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PhpTuf\ComposerStager\Helper\NamespaceHelper;
use PhpTuf\ComposerStager\Helper\PhpHelper;

/** Finds API code that depends on non-API layers, e.g., the internal layer or vendor code. */
final class APILayerDependsOnOtherLayerSniff implements Sniff
{
    private const CODE_DEPENDS_ON_NON_API_LAYER = 'DependsOnNonAPILayer';

    public function register(): array
    {
        return [T_USE];
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        // Not an API layer source file. (This apparently can't be controlled with
        // exclusions in phpcs.xml due to the way the custom sniffs are included.)
        if (!$this->isAPIFile($phpcsFile)) {
            return;
        }

        $namespace = NamespaceHelper::getNamespace($phpcsFile, $stackPtr);

        // Not a scope-level namespace, e.g., a class or interface.
        // (It's probably an anonymous function declaration.)
        if (!$this->isScopeLevelNamespace($phpcsFile, $stackPtr)) {
            return;
        }

        // The namespace is within the API layer.
        if ($this->isAPILayerNamespace($namespace)) {
            return;
        }

        // The non-API namespace is allowed.
        if ($this->isExcluded($namespace)) {
            return;
        }

        // The namespace is in the API layer.
        if ($this->namespaceIsInAPILayer($phpcsFile, $stackPtr)) {
            return;
        }

        $phpcsFile->addError(
            sprintf(
                'API-layer class %s must not depend on non-API-layer class %s.',
                $this->getClass($phpcsFile),
                $namespace,
            ),
            $stackPtr,
            self::CODE_DEPENDS_ON_NON_API_LAYER,
        );
    }

    private function isAPIFile(File $phpcsFile): bool
    {
        $srcDir = dirname(__DIR__, 4) . '/src/API';

        return str_starts_with($phpcsFile->getFilename(), $srcDir);
    }

    private function isScopeLevelNamespace(File $phpcsFile, int $stackPtr): bool
    {
        $scopePtr = $phpcsFile->findNext(Tokens::$ooScopeTokens, $stackPtr);

        return $scopePtr !== false;
    }

    private function isAPILayerNamespace(string $namespace): bool
    {
        return str_starts_with($namespace, 'PhpTuf\\ComposerStager\\API');
    }

    private function isExcluded(string $namespace): bool
    {
        return in_array($namespace, PhpHelper::UNAMBIGUOUS_BUILTIN_CLASSES, true);
    }

    private function namespaceIsInAPILayer(File $phpcsFile, int $stackPtr): bool
    {
        $endOfNamespaceDeclaration = NamespaceHelper::getEndOfNamespaceDeclaration($phpcsFile, $stackPtr);
        $lastStringPtr = $phpcsFile->findPrevious(T_STRING, $endOfNamespaceDeclaration);
        $asKeywordPtr = $phpcsFile->findPrevious(T_AS, $lastStringPtr, $stackPtr);

        return $asKeywordPtr !== false;
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
