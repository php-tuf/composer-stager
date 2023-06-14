<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PhpTuf\ComposerStager\Helper\DocCommentTag;

/** Finds "@property" tags that don't have a dollar sign on the variable name. */
final class PropertyVarNameHasDollarSignSniff implements Sniff
{
    private const CODE_MISSING_DOLLAR_SIGN = 'MissingDollarSign';

    public function register(): array
    {
        return [T_DOC_COMMENT_TAG];
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        $tag = new DocCommentTag($phpcsFile, $stackPtr);

        // Only process the target tag.
        if ($tag->getName() !== '@property') {
            return;
        }

        $content = $tag->getContent();
        $contentParts = explode(' ', $content);
        $contentParts = array_values((array) $contentParts);
        $varName = array_pop($contentParts);

        // The variable name (correctly) begins with a dollar sign.
        if (str_starts_with($varName, '$')) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            sprintf(
                '"@property" variable name must begin with a dollar sign, i.e., "$%s".',
                $varName,
            ),
            $stackPtr,
            self::CODE_MISSING_DOLLAR_SIGN,
        );

        // Exit early if not fixing.
        if (!$fix) {
            return;
        }

        $this->fix($phpcsFile, $tag, $contentParts, $varName);
    }

    private function fix(File $phpcsFile, DocCommentTag $tag, array $contentParts, string $varName): void
    {
        // Create the fixed token.
        $contentParts[] = '$' . $varName;
        $fixedToken = implode(' ', $contentParts);

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->replaceToken($tag->getStringPtr(), $fixedToken);
        $phpcsFile->fixer->endChangeset();
    }
}
