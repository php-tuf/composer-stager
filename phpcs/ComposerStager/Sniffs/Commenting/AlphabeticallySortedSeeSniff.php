<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPCS\ComposerStager\Sniffs\Commenting;

final class AlphabeticallySortedSeeSniff extends AbstractAlphabeticallySortedTagsSniff
{
    protected function errorCode(): string
    {
        return 'IncorrectlyOrderedSee';
    }

    protected function targetTag(): string
    {
        return '@see';
    }
}
