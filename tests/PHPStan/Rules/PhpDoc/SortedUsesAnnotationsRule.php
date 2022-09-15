<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPStan\Rules\PhpDoc;

/** Requires "@uses" annotations to be sorted alphabetically. */
final class SortedUsesAnnotationsRule extends AbstractSortedAnnotationsRule
{
    protected function targetTag(): string
    {
        return '@uses';
    }
}
