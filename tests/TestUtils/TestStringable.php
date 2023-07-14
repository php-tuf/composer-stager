<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

final class TestStringable
{
    public function __construct(private readonly string $string)
    {
    }

    public function __toString(): string
    {
        return $this->string;
    }
}
