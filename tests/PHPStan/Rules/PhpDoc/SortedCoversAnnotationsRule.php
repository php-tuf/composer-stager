<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPStan\Rules\PhpDoc;

/** Requires "@covers" annotations to be sorted alphabetically. */
final class SortedCoversAnnotationsRule extends AbstractSortedAnnotationsRule
{
    protected function targetTag(): string
    {
        return '@covers';
    }
}
