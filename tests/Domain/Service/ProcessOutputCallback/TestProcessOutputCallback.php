<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Domain\Service\ProcessOutputCallback;

use PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface;

final class TestProcessOutputCallback implements ProcessOutputCallbackInterface
{
    /** phpcs:disable SlevomatCodingStandard.Functions */
    public function __invoke(string $type, string $buffer): void
    {
    }
}
