<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Process\Service;

use PhpTuf\ComposerStager\API\Process\Value\OutputTypeEnum;
use PhpTuf\ComposerStager\Internal\Process\Service\OutputCallback;
use PhpTuf\ComposerStager\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(OutputCallback::class)]
final class OutputCallbackUnitTest extends TestCase
{
    #[DataProvider('providerBasicFunctionality')]
    public function testBasicFunctionality(
        array $givenOutput,
        array $expectedOutput,
        array $givenErrorOutput,
        array $expectedErrorOutput,
    ): void {
        $sut = new OutputCallback();

        self::assertOutput($sut, [], [], 'Started with empty output.');

        $this->invokeCallback($sut, OutputTypeEnum::OUT, $givenOutput);
        $this->invokeCallback($sut, OutputTypeEnum::ERR, $givenErrorOutput);

        self::assertOutput($sut, $expectedOutput, $expectedErrorOutput, 'Got output.');

        $this->invokeCallback($sut, OutputTypeEnum::OUT, $givenOutput);
        $this->invokeCallback($sut, OutputTypeEnum::ERR, $givenErrorOutput);

        self::assertOutput(
            $sut,
            array_merge($expectedOutput, $expectedOutput),
            array_merge($expectedErrorOutput, $expectedErrorOutput),
            'Kept output history.',
        );

        $sut->clearOutput();
        $sut->clearErrorOutput();

        self::assertOutput($sut, [], [], 'Cleared output.');
    }

    public static function providerBasicFunctionality(): array
    {
        // Space ( ).
        $s = "\040";

        return [
            'No output' => [
                'givenOutput' => [],
                'expectedOutput' => [],
                'givenErrorOutput' => [],
                'expectedErrorOutput' => [],
            ],
            'One line of output' => [
                'givenOutput' => ['out one'],
                'expectedOutput' => ['out one'],
                'givenErrorOutput' => ['err one'],
                'expectedErrorOutput' => ['err one'],
            ],
            'Multiple lines of output (including blank lines)' => [
                'givenOutput' => ['out one', '', 'out two'],
                'expectedOutput' => ['out one', '', 'out two'],
                'givenErrorOutput' => ['err one', '', 'err two'],
                'expectedErrorOutput' => ['err one', '', 'err two'],
            ],
            'Complex multiline buffers with tricky whitespace' => [
                'givenOutput' => [
                    <<<END
                    
                    
                    {$s}out one\t
                    {$s}
                    
                    out two \t
                    
                    
                    END,
                ],
                'expectedOutput' => ['', " out one\t", ' ', '', "out two \t", '', ''],
                'givenErrorOutput' => [
                    <<<END
                    \terr one{$s}
                    
                    {$s}
                    \terr two{$s}
                    
                    
                    END,
                ],
                'expectedErrorOutput' => ["\terr one ", '', ' ', "\terr two ", ''],
            ],
        ];
    }

    private function invokeCallback(OutputCallback $sut, OutputTypeEnum $type, array $buffers): void
    {
        foreach ($buffers as $buffer) {
            $sut($type, $buffer);
        }
    }

    private static function assertOutput(
        OutputCallback $sut,
        array $output,
        array $errorOutput,
        ?string $message = '',
    ): void {
        self::assertSame([
            OutputTypeEnum::OUT->name => $output,
            OutputTypeEnum::ERR->name => $errorOutput,
        ], [
            OutputTypeEnum::OUT->name => $sut->getOutput(),
            OutputTypeEnum::ERR->name => $sut->getErrorOutput(),
        ], $message);
    }
}
