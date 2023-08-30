<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Helper;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

final class NamespaceHelper
{
    public static function getNamespace(File $phpcsFile, int $scopePtr): string
    {
        $endOfNamespaceDeclaration = self::getEndOfNamespaceDeclaration($phpcsFile, $scopePtr);

        return self::getDeclarationNameWithNamespace(
            $phpcsFile->getTokens(),
            $endOfNamespaceDeclaration - 1,
        );
    }

    public static function getDeclarationNameWithNamespace(array $tokens, $stackPtr): string
    {
        $nameParts = [];
        $currentPointer = $stackPtr;

        while ($tokens[$currentPointer]['code'] === T_NS_SEPARATOR
            || $tokens[$currentPointer]['code'] === T_STRING
            || isset(Tokens::$emptyTokens[$tokens[$currentPointer]['code']])
        ) {
            if (isset(Tokens::$emptyTokens[$tokens[$currentPointer]['code']])) {
                --$currentPointer;

                continue;
            }

            $nameParts[] = $tokens[$currentPointer]['content'];
            --$currentPointer;
        }

        $nameParts = array_reverse($nameParts);

        return implode('', $nameParts);
    }

    public static function getEndOfNamespaceDeclaration(File $phpcsFile, int $scopePtr): int|false
    {
        return $phpcsFile->findNext([T_SEMICOLON, T_OPEN_CURLY_BRACKET], $scopePtr);
    }
}
