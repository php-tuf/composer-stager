<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPStan\Rules\PhpDoc;

/** Requires "@throws" annotations to be sorted alphabetically. */
final class SortedThrowsAnnotationsRule extends AbstractSortedAnnotationsRule
{
    protected function targetTag(): string
    {
        return '@throws';
    }
}
