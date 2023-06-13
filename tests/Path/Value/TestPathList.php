<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Path\Value;

use PhpTuf\ComposerStager\Domain\Path\Value\PathListInterface;

final class TestPathList implements PathListInterface
{
    public function getAll(): array
    {
        return [];
    }

    public function add(...$paths): void
    {
        // Unimplemented.
    }
}
