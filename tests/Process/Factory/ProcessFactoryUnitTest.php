<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Process\Factory;

use PhpTuf\ComposerStager\Internal\Process\Factory\ProcessFactory;
use PhpTuf\ComposerStager\Internal\Process\Factory\SymfonyProcessFactoryInterface;
use PhpTuf\ComposerStager\Internal\Process\Service\Process;
use PhpTuf\ComposerStager\Tests\Doubles\Process\Service\TestSymfonyProcess;
use PhpTuf\ComposerStager\Tests\Doubles\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

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
        $symfonyProcessFactory = $this->prophesize(SymfonyProcessFactoryInterface::class);
        assert(in_array($symfonyProcessFactory::class, [SymfonyProcessFactoryInterface::class, ObjectProphecy::class]));
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
            'Empty command' => [[]],
            'Simple command' => [['one']],
            'Command with options' => [['one', 'two', 'three']],
        ];
    }
}
