<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PhpTuf\ComposerStager\Helper\NamespaceHelper;
use PhpTuf\ComposerStager\Helper\PHPHelper;

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

        $namespace = $this->getNamespace($phpcsFile, $stackPtr);

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
                $namespace,
                $namespace,
            ),
            $stackPtr,
            self::CODE_DEPENDS_ON_NON_API_LAYER,
        );
    }

    private function isAPIFile(File $phpcsFile): bool
    {
        $srcDir = dirname(__DIR__, 4) . '/src/API';

        return str_starts_with((string) $phpcsFile->getFilename(), $srcDir);
    }

    private function getNamespace(File $phpcsFile, int $scopePtr): string
    {
        $endOfNamespaceDeclaration = NamespaceHelper::getEndOfNamespaceDeclaration($phpcsFile, $scopePtr);

        return NamespaceHelper::getDeclarationNameWithNamespace(
            $phpcsFile->getTokens(),
            $endOfNamespaceDeclaration - 1,
        );
    }

    private function isScopeLevelNamespace(File $phpcsFile, int $stackPtr): bool
    {
        $scopePtr = $phpcsFile->findNext(Tokens::$ooScopeTokens, $stackPtr);

        return $scopePtr !== false;
    }

    private function isAPILayerNamespace(string $namespace): bool
    {
        return str_starts_with($namespace, 'PhpTuf\\ComposerStager');
    }

    private function isExcluded(string $namespace): bool
    {
        return in_array($namespace, PHPHelper::UNAMBIGUOUS_BUILTIN_CLASSES, true);
    }

    private function namespaceIsInAPILayer(File $phpcsFile, int $stackPtr): bool
    {
        $endOfNamespaceDeclaration = NamespaceHelper::getEndOfNamespaceDeclaration($phpcsFile, $stackPtr);
        $lastStringPtr = $phpcsFile->findPrevious(T_STRING, $endOfNamespaceDeclaration);
        $asKeywordPtr = $phpcsFile->findPrevious(T_AS, $lastStringPtr, $stackPtr);

        return $asKeywordPtr !== false;
    }
}
