<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Value\PathList;

use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;

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
