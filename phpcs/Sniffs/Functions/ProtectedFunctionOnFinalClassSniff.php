<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/** Finds protected methods on final classes. */
final class ProtectedFunctionOnFinalClassSniff implements Sniff
{
    private const CODE_PROTECTED_METHOD = 'ProtectedMethod';

    public function register(): array
    {
        return [T_FUNCTION];
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $scopeModifierPtr = $phpcsFile->findPrevious(Tokens::$scopeModifiers, $stackPtr);
        $scopeModifier = $tokens[$scopeModifierPtr];

        // Method is not protected.
        if ($scopeModifier['code'] !== T_PROTECTED) {
            return;
        }

        $classPtr = $phpcsFile->findPrevious(T_CLASS, $stackPtr);

        // The file isn't a class--probably a trait.
        if ($classPtr === false) {
            return;
        }

        $classProperties = $phpcsFile->getClassProperties($classPtr);

        // Class is not final.
        if (array_key_exists('is_final', $classProperties) && $classProperties['is_final'] === false) {
            return;
        }

        $extendedClassName = $phpcsFile->findExtendedClassName($classPtr);

        // @todo This class extends another one that could force the method to be protected.
        //   This case is just ignored for now despite the fact that this could result in missing
        //   genuine failures. This can probably be overcome by getting the FQN of the ended
        //   class and finding and loading the result, then scanning it for the method in
        //   question and comparing its visibility--another day.
        if ($extendedClassName !== false) {
            return;
        }

        $nameNamePtr = $phpcsFile->findNext(T_STRING, $stackPtr);
        $methodName = $tokens[$nameNamePtr]['content'];

        $fix = $phpcsFile->addFixableError(
            sprintf(
                'Protected method ::%s() on final class should be private.',
                $methodName,
            ),
            $stackPtr,
            self::CODE_PROTECTED_METHOD,
        );

        if (!$fix) {
            return;
        }

        // Change visibility to final.
        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->replaceToken($scopeModifierPtr, 'private');
        $phpcsFile->fixer->endChangeset();
    }
}
