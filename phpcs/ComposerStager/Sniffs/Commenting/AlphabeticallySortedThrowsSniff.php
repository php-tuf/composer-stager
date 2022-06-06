<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPCS\ComposerStager\Sniffs\Commenting;

final class AlphabeticallySortedThrowsSniff extends AbstractAlphabeticallySortedTageSniff
{
    protected function errorCode(): string
    {
        return 'IncorrectlyOrderedThrows';
    }

    protected function targetTag(): string
    {
        return '@throws';
    }
}
