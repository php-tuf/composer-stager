<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Value\Path;

use PhpTuf\ComposerStager\Domain\Value\Path\PathListInterface;

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
