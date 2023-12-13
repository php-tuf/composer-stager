<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PhpTuf\ComposerStager\Helper\NamespaceHelper;

/** Finds "use" aliases that end with "Alias". */
final class AliasEndsWithAliasSniff implements Sniff
{
    private const CODE_ALIAS_ENDS_WITH_ALIAS = 'AliasEndsWithAlias';

    public function register(): array
    {
        return [T_USE];
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        // Not a project source file. (This apparently can't be controlled with
        // exclusions in phpcs.xml due to the way the custom sniffs are included.)
        if (!$this->isProjectFile($phpcsFile)) {
            return;
        }

        $namespace = NamespaceHelper::getNamespace($phpcsFile, $stackPtr);

        if (!str_ends_with($namespace, 'Alias')) {
            return;
        }

        // Not a scope-level namespace, e.g., a class or interface.
        // (It's probably an anonymous function declaration.)
        if (!$this->isScopeLevelNamespace($phpcsFile, $stackPtr)) {
            return;
        }

        // The namespace has no alias.
        if (!$this->aliasIsFound($phpcsFile, $stackPtr)) {
            return;
        }

        $phpcsFile->addError(
            sprintf(
                'Namespace alias %s must not end with the string "Alias".',
                $namespace,
            ),
            $stackPtr,
            self::CODE_ALIAS_ENDS_WITH_ALIAS,
        );
    }

    private function isProjectFile(File $phpcsFile): bool
    {
        $projectRoot = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR;
        $pattern = sprintf('#^%s#', preg_quote($projectRoot, DIRECTORY_SEPARATOR));
        $phpcsFileRelative = preg_replace($pattern, '', $phpcsFile->getFilename());
        $topLevelDir = strtok($phpcsFileRelative, DIRECTORY_SEPARATOR);

        return in_array($topLevelDir, ['src', 'tests', 'tools'], true);
    }

    private function isScopeLevelNamespace(File $phpcsFile, int $stackPtr): bool
    {
        $scopePtr = $phpcsFile->findNext(Tokens::$ooScopeTokens, $stackPtr);

        return $scopePtr !== false;
    }

    private function aliasIsFound(File $phpcsFile, int $stackPtr): bool
    {
        $endOfNamespaceDeclaration = NamespaceHelper::getEndOfNamespaceDeclaration($phpcsFile, $stackPtr);
        $lastStringPtr = $phpcsFile->findPrevious(T_STRING, $endOfNamespaceDeclaration);
        $asKeywordPtr = $phpcsFile->findPrevious(T_AS, $lastStringPtr, $stackPtr);

        return $asKeywordPtr !== false;
    }
}
