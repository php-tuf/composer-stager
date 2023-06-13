<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/** Finds commas immediately preceding closing parentheses on the same line, e.g., "($var,)". */
final class CommaBeforeClosingParenthesisSniff implements Sniff
{
    private const CODE_COMMA_BEFORE_CLOSING_PARENTHESIS = 'CommaBeforeClosingParenthesis';

    public function register(): array
    {
        return [T_CLOSE_PARENTHESIS];
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $closingParenthesis = $tokens[$stackPtr];

        $previousTokenPtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true);
        $previousToken = $tokens[$previousTokenPtr];
        $previousCode = $previousToken['code'];

        // Previous token is not a comma.
        if ($previousCode !== T_COMMA) {
            return;
        }

        // Comma is not on the same line.
        if ($previousToken['line'] !== $closingParenthesis['line']) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'Closing parenthesis is preceded by a trailing comma on the same line.',
            $stackPtr,
            self::CODE_COMMA_BEFORE_CLOSING_PARENTHESIS,
        );

        if (!$fix) {
            return;
        }

        // Delete the comma.
        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->replaceToken($previousTokenPtr, '');
        $phpcsFile->fixer->endChangeset();
    }
}
