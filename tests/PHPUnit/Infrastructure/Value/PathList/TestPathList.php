<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Value\PathList;

use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;

final class TestPathList implements PathListInterface
{
    public function getAll(): array
    {
        return [];
    }
}
