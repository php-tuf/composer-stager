<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Path\Value;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;

final class TestPath implements PathInterface
{
    public function __construct(private readonly string $path = 'test')
    {
    }

    public function absolute(): string
    {
        return $this->path;
    }

    public function isAbsolute(): bool
    {
        return true;
    }

    public function raw(): string
    {
        return $this->path;
    }

    public function relative(PathInterface $basePath): string
    {
        return $this->path;
    }
}
