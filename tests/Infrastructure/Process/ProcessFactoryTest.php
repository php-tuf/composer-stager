<?php

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Process;

use PhpTuf\ComposerStager\Infrastructure\Process\ProcessFactory;
use PhpTuf\ComposerStager\Tests\TestCase;
use Symfony\Component\Process\Process;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Process\ProcessFactory
 */
class ProcessFactoryTest extends TestCase
{
    /**
     * @covers ::create
     *
     * @dataProvider providerFactory
     */
    public function testFactory($command, $args): void
    {
        $sut = new ProcessFactory();

        $actual = $sut->create($command, ...$args);

        $expected = new Process($command, ...$args);
        self::assertEquals($expected, $actual);
    }

    public function providerFactory(): array
    {
        return [
            [[], []],
            [['lorem'], []],
            [['lorem', 'ipsum'], []],
            [[], ['/var/www']],
        ];
    }
}
