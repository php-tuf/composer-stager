<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Process\Service;

use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\Internal\Process\Factory\ProcessFactory;
use PhpTuf\ComposerStager\Tests\TestCase;

/** @coversNothing */
final class ProcessFunctionalTest extends TestCase
{
    private function createSut(array $command): ProcessInterface
    {
        $container = $this->getContainer();
        $container->compile();

        /** @var \PhpTuf\ComposerStager\Internal\Process\Factory\ProcessFactory $factory */
        $factory = $container->get(ProcessFactory::class);

        return $factory->create($command);
    }

    public function testGetOutput(): void
    {
        $command = 'ls';
        $expected = shell_exec($command);
        $sut = $this->createSut([$command]);

        $sut->mustRun();
        $actual = $sut->getOutput();

        self::assertSame($expected, $actual, 'Returned correct final output via getter.');
    }

    public function testOutputCallback(): void
    {
        $buffer = __METHOD__;
        $sut = $this->createSut(['echo', $buffer]);
        $outputCallback = new TestProcessOutputCallback();

        $sut->mustRun($outputCallback);
        $sut->mustRun($outputCallback);
        $sut->mustRun($outputCallback);

        $expected = array_fill(0, 3, $buffer);
        self::assertSame($expected, $outputCallback->getOutput(), 'Streamed correct output to callback.');
        self::assertSame([], $outputCallback->getErrorOutput(), 'Streamed correct error output to callback.');
    }
}
