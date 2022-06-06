<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPCS\ComposerStager\Sniffs\Commenting;

final class AlphabeticallySortedPropertySniff extends AbstractAlphabeticallySortedTageSniff
{
    protected function errorCode(): string
    {
        return 'IncorrectlyOrderedProperty';
    }

    protected function targetTag(): string
    {
        return '@property';
    }
}
