<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Process\Factory;

use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\Internal\Process\Factory\SymfonyProcessFactory;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestDoubles\TestSpyInterface;
use PhpTuf\ComposerStager\Tests\TestUtils\BuiltinFunctionMocker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Symfony\Component\Process\Exception\LogicException as SymfonyLogicException;
use Symfony\Component\Process\Process as SymfonyProcess;

#[CoversClass(SymfonyProcessFactory::class)]
final class SymfonyProcessFactoryUnitTest extends TestCase
{
    #[DataProvider('providerBasicFunctionality')]
    public function testBasicFunctionality(array $given, array $expected): void
    {
        $translatableFactory = self::createTranslatableFactory();
        $sut = new SymfonyProcessFactory($translatableFactory);

        $actual = $sut->create(...$given);

        $expected = new SymfonyProcess(...$expected);
        self::assertEquals($expected, $actual);
        self::assertTranslatableAware($sut);
    }

    public static function providerBasicFunctionality(): array
    {
        return [
            'Minimum values' => [
                'given' => [['one']],
                'expected' => [['one'], null, []],
            ],
            'Default values' => [
                'given' => [['one'], null, []],
                'expected' => [['one'], null, []],
            ],
            'Simple values' => [
                'given' => [
                    ['one'],
                    self::arbitraryDirPath(),
                    ['ONE' => 'one', 'TWO' => 'two'],
                ],
                'expected' => [
                    ['one'],
                    self::arbitraryDirAbsolute(),
                    ['ONE' => 'one', 'TWO' => 'two'],
                ],
            ],
        ];
    }

    #[RunInSeparateProcess]
    public function testFailure(): void
    {
        $previousMessage = 'Something went wrong';
        $previous = new SymfonyLogicException($previousMessage);
        BuiltinFunctionMocker::mock(['getcwd' => $this->prophesize(TestSpyInterface::class)]);
        BuiltinFunctionMocker::$spies['getcwd']
            ->report()
            ->shouldBeCalledOnce()
            ->willThrow($previous);
        $translatableFactory = self::createTranslatableFactory();
        $sut = new SymfonyProcessFactory($translatableFactory);

        $message = sprintf('Failed to create process: %s', $previousMessage);
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->create(['arbitrary', 'command']);
        }, LogicException::class, $message);
    }
}
