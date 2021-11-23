<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Domain;

use PhpTuf\ComposerStager\Domain\Process\OutputCallbackInterface;

class TestOutputCallback implements OutputCallbackInterface
{
    public function __invoke(string $type, string $buffer): void
    {
    }
}
