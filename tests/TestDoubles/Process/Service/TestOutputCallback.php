<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestDoubles\Process\Service;

use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Value\OutputTypeEnum;

final class TestOutputCallback implements OutputCallbackInterface
{
    private const OUT = 'OUT';
    private const ERR = 'ERR';

    /** @var array{'OUT': array<string>, 'ERR': array<string>} */
    private array $output = [
        self::OUT => [],
        self::ERR => [],
    ];

    public function __invoke(OutputTypeEnum $type, string $buffer): void
    {
        $stringType = $type === OutputTypeEnum::OUT
            ? self::OUT
            : self::ERR;

        // Avoid OS-sensitivity and simplify comparison by stripping line endings.
        $this->output[$stringType][] = rtrim($buffer);
    }

    public function getErrorOutput(): array
    {
        return $this->output[self::ERR];
    }

    public function getOutput(): array
    {
        return $this->output[self::OUT];
    }
}
