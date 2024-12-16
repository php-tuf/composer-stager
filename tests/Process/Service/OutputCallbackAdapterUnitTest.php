<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Process\Service;

use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Value\OutputTypeEnum;
use PhpTuf\ComposerStager\Internal\Process\Service\OutputCallbackAdapter;
use PhpTuf\ComposerStager\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process as SymfonyProcess;

#[CoversClass(OutputCallbackAdapter::class)]
final class OutputCallbackAdapterUnitTest extends TestCase
{
    #[DataProvider('providerBasicFunctionality')]
    public function testBasicFunctionality(string $givenType, OutputTypeEnum $expectedType, string $buffer): void
    {
        $callback = $this->prophesize(OutputCallbackInterface::class);
        $callback->__invoke($expectedType, $buffer)
            ->shouldBeCalledOnce();
        $callback = $callback->reveal();
        $sut = new OutputCallbackAdapter($callback);

        $sut($givenType, $buffer);
    }

    public static function providerBasicFunctionality(): array
    {
        return [
            'stdout' => [
                'givenType' => SymfonyProcess::OUT,
                'expectedType' => OutputTypeEnum::OUT,
                'buffer' => 'stdout',
            ],
            'stderr' => [
                'givenType' => SymfonyProcess::ERR,
                'expectedType' => OutputTypeEnum::ERR,
                'buffer' => 'stderr',
            ],
        ];
    }

    #[DataProvider('providerNoCallback')]
    public function testNoCallback(array $constructorArguments): void
    {
        // No explicit assertion is necessary for this test. The SUT will
        // throw an exception if the behavior under doesn't behave as expected.
        $this->expectNotToPerformAssertions();

        $sut = new OutputCallbackAdapter(...$constructorArguments);

        $sut(SymfonyProcess::OUT, __METHOD__);
    }

    public static function providerNoCallback(): array
    {
        return [
            'Implicit null' => [
                'constructorArguments' => [],
            ],
            'Explicit null' => [
                'constructorArguments' => [null],
            ],
        ];
    }
}
