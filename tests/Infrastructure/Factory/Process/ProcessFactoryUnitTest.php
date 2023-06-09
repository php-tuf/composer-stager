<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Factory\Process;

use PhpTuf\ComposerStager\Infrastructure\Factory\Process\ProcessFactory;
use PhpTuf\ComposerStager\Tests\Infrastructure\Factory\Translation\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\TestCase;
use Symfony\Component\Process\Process;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Factory\Process\ProcessFactory
 *
 * @uses \PhpTuf\ComposerStager\Domain\Factory\Translation\TranslatableAwareTrait
 */
final class ProcessFactoryUnitTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::create
     *
     * @dataProvider providerFactory
     */
    public function testFactory(array $command): void
    {
        $translatableFactory = new TestTranslatableFactory();
        $sut = new ProcessFactory($translatableFactory);

        $actual = $sut->create($command);

        $expected = new Process($command);
        self::assertEquals($expected, $actual);
    }

    public function providerFactory(): array
    {
        return [
            [[]],
            [['one']],
            [['one', 'two']],
        ];
    }
}
