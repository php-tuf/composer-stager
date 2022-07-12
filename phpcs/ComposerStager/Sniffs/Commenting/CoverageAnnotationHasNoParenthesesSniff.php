<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPCS\ComposerStager\Sniffs\Commenting;

use ComposerStager\Sniffs\Util\DocCommentTag;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

final class CoverageAnnotationHasNoParenthesesSniff implements Sniff
{
    public function register(): array
    {
        return [T_DOC_COMMENT_TAG];
    }

    public function process(File $phpcsFile, $stackPtr)
    {
        $tag = new DocCommentTag($phpcsFile, $stackPtr);

        // Only process the target tag.
        if (!in_array($tag->getName(), ['@covers', '@uses'], true)) {
            return;
        }

        $content = $tag->getContent();
        $content = explode(' ', $content);
        $content = array_filter($content);
        $content = array_values((array) $content);

        $funcName = array_pop($content);

        // The function name does not end with parentheses.
        if (substr($funcName, -2) !== '()') {
            return;
        }

        $phpcsFile->addError(
            sprintf(
                '%s function name "%s" must not end with parentheses.',
                $tag->getName(),
                $funcName,
            ),
            $stackPtr,
            $this->errorCode(),
        );
    }

    protected function errorCode(): string
    {
        return 'ForbiddenParentheses';
    }
}
