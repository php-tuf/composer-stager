<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Process\Factory;

use PhpTuf\ComposerStager\Internal\Process\Factory\ProcessFactory;
use PhpTuf\ComposerStager\Internal\Process\Factory\SymfonyProcessFactoryInterface;
use PhpTuf\ComposerStager\Internal\Process\Service\Process;
use PhpTuf\ComposerStager\Tests\Process\Service\TestSymfonyProcess;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use Prophecy\Argument;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Process\Factory\ProcessFactory */
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
        /** @var \PhpTuf\ComposerStager\Internal\Process\Factory\SymfonyProcessFactoryInterface|\Prophecy\Prophecy\ObjectProphecy $symfonyProcessFactory */
        $symfonyProcessFactory = $this->prophesize(SymfonyProcessFactoryInterface::class);
        $symfonyProcessFactory
            ->create(Argument::cetera())
            ->willReturn(new TestSymfonyProcess());
        $symfonyProcessFactory = $symfonyProcessFactory->reveal();
        $translatableFactory = new TestTranslatableFactory();
        $sut = new ProcessFactory($symfonyProcessFactory, $translatableFactory);

        $actual = $sut->create($command);

        $expected = new Process($symfonyProcessFactory, $translatableFactory, $command);
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
