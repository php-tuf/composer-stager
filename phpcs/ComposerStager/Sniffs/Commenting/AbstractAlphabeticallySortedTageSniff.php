<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPCS\ComposerStager\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

abstract class AbstractAlphabeticallySortedTageSniff implements Sniff
{
    final public function register(): array
    {
        return [T_DOC_COMMENT_TAG];
    }

    abstract protected function errorCode(): string;

    abstract protected function targetTag(): string;

    public function process(File $phpcsFile, $stackPtr): void
    {
        // Only process @throws tags.
        if ($this->isTargetTag($phpcsFile, $stackPtr) === false) {
            return;
        }

        $current = $this->getCurrentAnnotation($phpcsFile, $stackPtr);
        $previous = $this->getPreviousThrows($phpcsFile, $stackPtr);

        // Ignore the first @throws tag in a doc comment.
        if ($previous === false) {
            return;
        }

        // Current tag (correctly) comes alphabetically after previous.
        if (strcmp(strtolower($current), strtolower($previous)) > 0) {
            return;
        }

        $phpcsFile->addError(
            sprintf(
                '%s annotations should be sorted alphabetically. The first wrong one is %s.',
                $this->targetTag(),
                $current,
            ),
            $stackPtr,
            $this->errorCode(),
        );
    }

    private function getCurrentAnnotation(File $phpcsFile, $stackPtr): string
    {
        $tokens = $phpcsFile->getTokens();
        $commentStringPtr = $phpcsFile->findNext(T_DOC_COMMENT_STRING, $stackPtr);

        return trim($tokens[$commentStringPtr]['content']);
    }

    protected function getPreviousThrows(File $phpcsFile, int $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $commentOpenPtr = $phpcsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, $stackPtr);
        $commentOpen = $tokens[$commentOpenPtr]['line'];

        $previousTagPtr = $phpcsFile->findPrevious(T_DOC_COMMENT_TAG, $stackPtr - 1, $commentOpen);

        // Doc block is a single line.
        if ($tokens[$previousTagPtr]['line'] < $tokens[$commentOpenPtr]['line']) {
            return false;
        }

        if ($this->isTargetTag($phpcsFile, $previousTagPtr) === false) {
            return false;
        }

        $previousPtr = $phpcsFile->findNext(T_DOC_COMMENT_STRING, $previousTagPtr);

        return trim($tokens[$previousPtr]['content']);
    }

    private function isTargetTag(File $phpcsFile, $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();

        return $tokens[$stackPtr]['content'] === $this->targetTag();
    }
}
