<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Process\Service;

use PhpTuf\ComposerStager\API\Process\Service\ProcessOutputCallbackInterface;

final class TestProcessOutputCallback implements ProcessOutputCallbackInterface
{
    /** @var array{'out': array<string>, 'err': array<string>} */
    private array $output = [
        'out' => [],
        'err' => [],
    ];

    /** @phpcs:disable SlevomatCodingStandard.Functions */
    public function __invoke(string $type, string $buffer): void
    {
        // Avoid OS-sensitivity and simplify comparison by stripping line endings.
        $this->output[$type][] = rtrim($buffer);
    }

    public function getErrorOutput(): array
    {
        return $this->output['err'];
    }

    public function getOutput(): array
    {
        return $this->output['out'];
    }
}
