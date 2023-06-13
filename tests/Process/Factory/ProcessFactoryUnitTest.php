<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Process\Factory;

use PhpTuf\ComposerStager\Internal\Process\Factory\ProcessFactory;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use Symfony\Component\Process\Process;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Process\Factory\ProcessFactory
 *
 * @uses \PhpTuf\ComposerStager\API\Translation\Factory\TranslatableAwareTrait
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
