<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Process\Service;

use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\Internal\Process\Factory\ProcessFactory;
use PhpTuf\ComposerStager\Internal\Process\Service\OutputCallback;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerTestHelper;

/** @coversNothing */
final class ProcessFunctionalTest extends TestCase
{
    private function createSut(array $command): ProcessInterface
    {
        $factory = ContainerTestHelper::get(ProcessFactory::class);
        assert($factory instanceof ProcessFactory);

        return $factory->create($command);
    }

    public function testGetOutput(): void
    {
        $expectedOutput = trim(shell_exec('echo test'));
        $expectedStatusCode = 0;
        $sut = $this->createSut(['echo', 'test']);

        $actualStatusCode = $sut->run();
        $actualOutput = trim($sut->getOutput());

        self::assertSame($expectedStatusCode, $actualStatusCode, 'Returned correct final output via getter.');
        self::assertSame($expectedOutput, $actualOutput, 'Returned correct final output via getter.');
    }

    public function testOutputCallbackStdout(): void
    {
        $buffer = __METHOD__;
        $sut = $this->createSut(['echo', $buffer]);
        $outputCallback = new OutputCallback();

        $sut->run($outputCallback);
        $sut->mustRun($outputCallback);

        $expected = array_fill(0, 2, $buffer);
        self::assertSame($expected, $outputCallback->getOutput(), 'Streamed correct output to callback.');
        self::assertSame([], $outputCallback->getErrorOutput(), 'Streamed correct error output to callback.');
    }

    public function testOutputCallbackStderr(): void
    {
        $invalidCommand = 'invalid_command';
        $sut = $this->createSut([$invalidCommand]);
        $outputCallback = new OutputCallback();

        $sut->run($outputCallback);

        self::assertSame([], $outputCallback->getOutput(), 'Streamed correct output to callback.');
        // Asserting on error output is a little tricky across platforms. The safest meaningful
        // test is probably to just search the error output for the invalid command name.
        self::assertStringContainsString($invalidCommand, $outputCallback->getErrorOutput()[0], 'Streamed correct error output to callback.');
    }
}
