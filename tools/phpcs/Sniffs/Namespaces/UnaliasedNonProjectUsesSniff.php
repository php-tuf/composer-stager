<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PhpTuf\ComposerStager\Helper\NamespaceHelper;

/** Finds unaliased "use" statements for non-project code, e.g., vendor libraries and PHP itself. */
final class UnaliasedNonProjectUsesSniff implements Sniff
{
    private const CODE_UNALIASED_NON_PROJECT_USES = 'UnaliasedNonProjectUses';
    private const ALLOWED_UNALIASED = [
        'stdClass',
        'Stringable',
        'FilesystemIterator',
        'RecursiveCallbackFilterIterator',
        'RecursiveDirectoryIterator',
        'RecursiveIteratorIterator',
        'Throwable',
    ];

    public function register(): array
    {
        return [T_USE];
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        // Not a project source file. (This apparently can't be controlled with
        // exclusions in phpcs.xml due to the way the custom sniffs are included.)
        if (!$this->isSrcFile($phpcsFile)) {
            return;
        }

        $namespace = NamespaceHelper::getNamespace($phpcsFile, $stackPtr, $this);

        // Not a scope-level namespace, e.g., a class or interface.
        // (It's probably an anonymous function declaration.)
        if (!$this->isScopeLevelNamespace($phpcsFile, $stackPtr)) {
            return;
        }

        // The namespace is within the project.
        if ($this->isProjectNamespace($namespace)) {
            return;
        }

        // The non-project namespace is allowed.
        if ($this->isAllowedWithoutAlias($namespace)) {
            return;
        }

        // The namespace has an alias.
        if ($this->aliasIsFound($phpcsFile, $stackPtr)) {
            return;
        }

        $phpcsFile->addError(
            sprintf(
                'Non-project namespace %s must be used with an alias.',
                $namespace,
            ),
            $stackPtr,
            self::CODE_UNALIASED_NON_PROJECT_USES,
        );
    }

    private function isSrcFile(File $phpcsFile): bool
    {
        $srcDir = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'src';

        return str_starts_with((string) $phpcsFile->getFilename(), $srcDir);
    }

    private function isScopeLevelNamespace(File $phpcsFile, int $stackPtr): bool
    {
        $scopePtr = $phpcsFile->findNext(Tokens::$ooScopeTokens, $stackPtr);

        return $scopePtr !== false;
    }

    private function isProjectNamespace(string $namespace): bool
    {
        return str_starts_with($namespace, 'PhpTuf\\ComposerStager');
    }

    private function isAllowedWithoutAlias(string $namespace): bool
    {
        return in_array($namespace, self::ALLOWED_UNALIASED, true);
    }

    private function aliasIsFound(File $phpcsFile, int $stackPtr): bool
    {
        $endOfNamespaceDeclaration = NamespaceHelper::getEndOfNamespaceDeclaration($phpcsFile, $stackPtr);
        $lastStringPtr = $phpcsFile->findPrevious(T_STRING, $endOfNamespaceDeclaration);
        $asKeywordPtr = $phpcsFile->findPrevious(T_AS, $lastStringPtr, $stackPtr);

        return $asKeywordPtr !== false;
    }
}
