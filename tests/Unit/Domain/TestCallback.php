<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Domain;

use PhpTuf\ComposerStager\Domain\Output\CallbackInterface;

class TestCallback implements CallbackInterface
{
    public function __invoke(string $type, string $buffer): void
    {
    }
}
