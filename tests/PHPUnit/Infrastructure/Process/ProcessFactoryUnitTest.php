<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Process;

use PhpTuf\ComposerStager\Infrastructure\Process\ProcessFactory;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Symfony\Component\Process\Process;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Process\ProcessFactory
 */
class ProcessFactoryUnitTest extends TestCase
{
    /**
     * @covers ::create
     *
     * @dataProvider providerFactory
     */
    public function testFactory($command): void
    {
        $sut = new ProcessFactory();

        $actual = $sut->create($command);

        $expected = new Process($command);
        self::assertEquals($expected, $actual);
    }

    public function providerFactory(): array
    {
        return [
            [[]],
            [['lorem']],
            [['lorem', 'ipsum']],
        ];
    }
}
