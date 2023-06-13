<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Process\Service;

use PhpTuf\ComposerStager\API\Process\Service\ProcessOutputCallbackInterface;

final class TestProcessOutputCallback implements ProcessOutputCallbackInterface
{
    /** phpcs:disable SlevomatCodingStandard.Functions */
    public function __invoke(string $type, string $buffer): void
    {
    }
}
