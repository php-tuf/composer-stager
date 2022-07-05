<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPCS\ComposerStager\Sniffs\Commenting;

final class AlphabeticallySortedCoversSniff extends AbstractAlphabeticallySortedTagsSniff
{
    protected function errorCode(): string
    {
        return 'IncorrectlyOrderedCovers';
    }

    protected function targetTag(): string
    {
        return '@covers';
    }
}
