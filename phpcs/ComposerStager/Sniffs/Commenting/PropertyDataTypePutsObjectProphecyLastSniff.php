<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPCS\ComposerStager\Sniffs\Commenting;

use ComposerStager\Sniffs\Util\DocCommentTag;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

final class PropertyDataTypePutsObjectProphecyLastSniff implements Sniff
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

        // No data type present.
        if (count($content) < 2) {
            return;
        }

        // Remove variable name from the end.
        array_pop($content);

        $dataTypes = explode('|', $content[0]);

        // Remove the last data type.
        array_pop($dataTypes);

        // Check the rest for ObjectProphecy.
        foreach ($dataTypes as $dataType) {
            $fqnParts = explode('\\', $dataType);
            $className = end($fqnParts);

            if ($className !== 'ObjectProphecy') {
                continue;
            }

            $phpcsFile->addError(
                sprintf(
                    '"ObjectProphecy" should be last in the list of @property data types in "%s".',
                    implode('\\', $content),
                ),
                $stackPtr,
                $this->errorCode(),
            );
        }
    }

    protected function errorCode(): string
    {
        return 'ObjectProphecyIsNotLastDataType';
    }
}
