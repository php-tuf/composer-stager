<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

final class AlphabeticallySortedTagsSniff implements Sniff
{
    private const TAGS = [
        '@covers',
        '@property',
        '@see',
        '@throws',
        '@uses',
    ];

    public function register(): array
    {
        return [T_DOC_COMMENT_OPEN_TAG];
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        foreach (self::TAGS as $tag) {
            $this->processTag($phpcsFile, $stackPtr, $tag);
        }
    }

    private function processTag(File $phpcsFile, $stackPtr, string $tagName): void
    {
        $tokens = $phpcsFile->getTokens();

        $docBlock = $tokens[$stackPtr];
        //$originalCoversTagsPtrs = [];
        $originalCoversTagValues = [];
        $previousCoversTagContent = '';
        $fix = false;
        $errorsFound = 0;

        // Get the target tags.
        foreach ($docBlock['comment_tags'] as $tagPtr) {
            $tag = $tokens[$tagPtr];

            // Ignore any other tags.
            if ($tag['content'] !== $tagName) {
                continue;
            }

            // Get the tag value.
            $tagValuePtr = $phpcsFile->findNext(T_DOC_COMMENT_STRING, $tagPtr);
            $tagValue = $tokens[$tagValuePtr]['content'];

            //$originalCoversTagPtrs[] = $tagValuePtr;
            $originalCoversTagValues[] = $tagValue;

            // If the current tag should be before the previous one.
            if (strcasecmp((string) $tagValue, (string) $previousCoversTagContent) < 0) {
                $errorsFound++;

                // Only report the first error found.
                if ($errorsFound === 1) {
                    // @todo Why does this always come back as false?
                    //$fix = $phpcsFile->addFixableError(sprintf(
                    $phpcsFile->addError(sprintf(
                        '%s annotations should be sorted alphabetically. The first wrong one is %s.',
                        $tagName,
                        $tagValue,
                    ), $tagPtr, sprintf(
                        'IncorrectlyOrdered%sTags',
                        ucfirst(trim($tagName, '@')),
                    ));
                }
            }

            $previousCoversTagContent = $tagValue;
        }

        if ($errorsFound === 0) {
            return;
        }

        if (!$fix) {
            return;
        }

        // Sort the tags.
        $sortedCoversTagsContents = $originalCoversTagValues;
        arsort($sortedCoversTagsContents);
    }
}
