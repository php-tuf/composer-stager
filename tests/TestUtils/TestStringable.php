<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

use Stringable;

final class TestStringable implements Stringable
{
    public function __construct(private readonly string $string)
    {
    }

    public function __toString(): string
    {
        return $this->string;
    }
}
