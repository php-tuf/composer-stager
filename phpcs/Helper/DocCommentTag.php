<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Helper;

use PHP_CodeSniffer\Files\File;

final class DocCommentTag
{
    private readonly array $tokens;

    public function __construct(private readonly File $phpcsFile, private readonly int $stackPtr)
    {
        $this->tokens = $this->phpcsFile->getTokens();
    }

    public function getName(): string
    {
        return trim($this->tokens[$this->stackPtr]['content']);
    }

    public function getContent(): string
    {
        $commentStringPtr = $this->getStringPtr();

        return trim($this->tokens[$commentStringPtr]['content']);
    }

    public function getStringPtr(): int
    {
        return $this->phpcsFile->findNext(T_DOC_COMMENT_STRING, $this->stackPtr);
    }

    public function getPrevious(): ?self
    {
        // Find first line of the docblock.
        $commentOpenPtr = $this->phpcsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, $this->stackPtr);
        $commentOpen = $this->tokens[$commentOpenPtr]['line'];

        // Search for previous tags within the docblock.
        $previousTagPtr = $this->phpcsFile->findPrevious(T_DOC_COMMENT_TAG, $this->stackPtr - 1, $commentOpen);

        // Doc block is a single line. There is no previous tag.
        if ($this->tokens[$previousTagPtr]['line'] < $this->tokens[$commentOpenPtr]['line']) {
            return null;
        }

        // Previous tag is not the same kind.
        if ($this->tokens[$previousTagPtr]['content'] !== $this->getName()) {
            return null;
        }

        return new self($this->phpcsFile, $previousTagPtr);
    }
}
