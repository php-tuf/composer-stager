<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/** Finds select docblocks tags that are out of alphabetical order. */
final class AlphabeticallySortedTagsSniff implements Sniff
{
    private const CODE_INCORRECTLY_ORDERED_TAGS = 'IncorrectlyOrdered%sTags';

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
        foreach (self::TAGS as $tagName) {
            $this->processTag($phpcsFile, $stackPtr, $tagName);
        }
    }

    private function processTag(File $phpcsFile, $stackPtr, string $tagName): void
    {
        $tokens = $phpcsFile->getTokens();

        $docBlock = $tokens[$stackPtr];
        $originalCoversTagValuePtrs = [];
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

            $originalCoversTagValuePtrs[] = $tagValuePtr;
            $originalCoversTagValues[] = $tagValue;

            // If the current tag should be before the previous one.
            if (strcasecmp((string) $tagValue, (string) $previousCoversTagContent) < 0) {
                $errorsFound++;

                // Only report the first error found.
                if ($errorsFound === 1) {
                    $fix = $phpcsFile->addFixableError(sprintf(
                        '%s annotations should be sorted alphabetically. The first wrong one is %s.',
                        $tagName,
                        $tagValue,
                    ), $tagPtr, sprintf(
                        self::CODE_INCORRECTLY_ORDERED_TAGS,
                        ucfirst(ltrim($tagName, '@')),
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

        $this->fix($originalCoversTagValues, $originalCoversTagValuePtrs, $phpcsFile);
    }

    private function fix(array $originalCoversTagValues, array $originalCoversTagValuePtrs, File $phpcsFile): void
    {
        // Sort the tags.
        $sortedCoversTagValues = $originalCoversTagValues;
        asort($sortedCoversTagValues);

        // Associate the original positions with their new values.
        $newCoversTagValues = array_combine($originalCoversTagValuePtrs, $sortedCoversTagValues);

        $phpcsFile->fixer->beginChangeset();

        // Replace the original tokens with their newly-sorted values.
        foreach ($newCoversTagValues as $originalPtr => $newValue) {
            $phpcsFile->fixer->replaceToken($originalPtr, $newValue);
        }

        $phpcsFile->fixer->endChangeset();
    }
}
