<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Domain;

use PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface;

class TestProcessOutputCallback implements ProcessOutputCallbackInterface
{
    public function __invoke(string $type, string $buffer): void
    {
    }
}
