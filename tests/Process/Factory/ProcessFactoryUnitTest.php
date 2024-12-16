<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Process\Factory;

use PhpTuf\ComposerStager\Internal\Process\Factory\ProcessFactory;
use PhpTuf\ComposerStager\Internal\Process\Factory\SymfonyProcessFactory;
use PhpTuf\ComposerStager\Internal\Process\Service\Process;
use PhpTuf\ComposerStager\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(ProcessFactory::class)]
final class ProcessFactoryUnitTest extends TestCase
{
    #[DataProvider('providerFactory')]
    public function testFactory(array $command, array $optionalArguments): void
    {
        $translatableFactory = self::createTranslatableFactory();
        $symfonyProcessFactory = new SymfonyProcessFactory($translatableFactory);
        $sut = new ProcessFactory($symfonyProcessFactory, $translatableFactory);

        $actualProcess = $sut->create($command, ...$optionalArguments);

        $expectedProcess = new Process($symfonyProcessFactory, $translatableFactory, $command, ...$optionalArguments);
        self::assertEquals($expectedProcess, $actualProcess);
        self::assertTranslatableAware($sut);
    }

    public static function providerFactory(): array
    {
        return [
            'Minimum values' => [
                'command' => ['one'],
                'optionalArguments' => [],
            ],
            'Default values' => [
                'command' => ['one'],
                'optionalArguments' => [null, []],
            ],
            'Simple command' => [
                'command' => ['one'],
                'optionalArguments' => [],
            ],
            'Command with options' => [
                'command' => ['one', 'two', 'three'],
                'optionalArguments' => [],
            ],
            'Command plus optional arguments' => [
                'command' => ['one'],
                'optionalArguments' => [null, ['TWO' => 'two']],
            ],
        ];
    }
}
