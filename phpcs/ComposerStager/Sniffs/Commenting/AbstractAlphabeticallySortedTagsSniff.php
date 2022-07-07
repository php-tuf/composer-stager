<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPCS\ComposerStager\Sniffs\Commenting;

use ComposerStager\Sniffs\Util\DocCommentTag;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

abstract class AbstractAlphabeticallySortedTagsSniff implements Sniff
{
    abstract protected function errorCode(): string;

    abstract protected function targetTag(): string;

    final public function register(): array
    {
        return [T_DOC_COMMENT_TAG];
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        $tag = new DocCommentTag($phpcsFile, $stackPtr);

        // Only process the target tag.
        if ($tag->getName() !== $this->targetTag()) {
            return;
        }

        $previous = $tag->getPrevious();

        // Ignore the first tag in a docblock.
        if ($previous === null) {
            return;
        }

        $current = strtolower($tag->getContent());
        $previous = strtolower($previous->getContent());

        // Current tag (correctly) comes alphabetically after previous.
        if (strcmp($current, $previous) > 0) {
            return;
        }

        $phpcsFile->addError(
            sprintf(
                '%s annotations should be sorted alphabetically. The first wrong one is %s.',
                $this->targetTag(),
                $tag->getContent(),
            ),
            $stackPtr,
            $this->errorCode(),
        );
    }
}
