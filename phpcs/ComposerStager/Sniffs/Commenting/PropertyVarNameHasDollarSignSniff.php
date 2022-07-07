<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPCS\ComposerStager\Sniffs\Commenting;

use ComposerStager\Sniffs\Util\DocCommentTag;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

final class PropertyVarNameHasDollarSignSniff implements Sniff
{
    public function register(): array
    {
        return [T_DOC_COMMENT_TAG];
    }

    public function process(File $phpcsFile, $stackPtr)
    {
        $tag = new DocCommentTag($phpcsFile, $stackPtr);

        // Only process the target tag.
        if ($tag->getName() !== '@property') {
            return;
        }

        $content = $tag->getContent();
        $content = explode(' ', $content);
        $content = array_filter($content);
        $content = array_values((array) $content);

        $varName = $content[1];

        // The variable name (correctly) begins with a dollar sign.
        if (strpos($varName, '$') === 0) {
            return;
        }

        $phpcsFile->addError(
            sprintf(
                '@property variable name must begin with a dollar sign, i.e., "$%s".',
                $varName,
            ),
            $stackPtr,
            $this->errorCode(),
        );
    }

    protected function errorCode(): string
    {
        return 'IncorrectlyOrderedClassParam';
    }
}
