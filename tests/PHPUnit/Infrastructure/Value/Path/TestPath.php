<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Value\Path;

use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

final class TestPath implements PathInterface
{
    private string $path;

    public function __construct(string $path = 'test')
    {
        $this->path = $path;
    }

    public function resolve(): string
    {
        return $this->path;
    }

    public function resolveRelativeTo(PathInterface $path): string
    {
        return $this->path;
    }
}
