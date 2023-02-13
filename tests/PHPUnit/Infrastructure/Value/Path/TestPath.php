<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Value\Path;

use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

final class TestPath implements PathInterface
{
    private bool $isAbsolute;

    private string $path;

    public function __construct(string $path = 'test', bool $isAbsolute = true)
    {
        $this->path = $path;
        $this->isAbsolute = $isAbsolute;
    }

    public function isAbsolute(): bool
    {
        return $this->isAbsolute;
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
