<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPCS\ComposerStager\Sniffs\Commenting;

final class AlphabeticallySortedUsesSniff extends AbstractAlphabeticallySortedTagsSniff
{
    protected function errorCode(): string
    {
        return 'IncorrectlyOrderedUses';
    }

    protected function targetTag(): string
    {
        return '@uses';
    }
}
