<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Process\Factory;

use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\Internal\Process\Factory\SymfonyProcessFactory;
use PhpTuf\ComposerStager\Tests\Doubles\TestSpyInterface;
use PhpTuf\ComposerStager\Tests\Doubles\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\BuiltinFunctionMocker;
use Symfony\Component\Process\Exception\LogicException as SymfonyLogicException;
use Symfony\Component\Process\Process;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Process\Factory\SymfonyProcessFactory */
final class SymfonyProcessFactoryUnitTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::create
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality(array $command): void
    {
        $translatableFactory = new TestTranslatableFactory();
        $sut = new SymfonyProcessFactory($translatableFactory);

        $actual = $sut->create($command);

        $expected = new Process($command);
        self::assertEquals($expected, $actual);
        self::assertTranslatableAware($sut);
    }

    public function providerBasicFunctionality(): array
    {
        return [
            'Empty command' => [[]],
            'Simple command' => [['one']],
            'Command with options' => [['one', 'two', 'three']],
        ];
    }

    /**
     * @covers ::create
     *
     * @runInSeparateProcess
     */
    public function testFailure(): void
    {
        $previousMessage = 'Something went wrong';
        $previous = new SymfonyLogicException($previousMessage);
        BuiltinFunctionMocker::mock(['getcwd' => $this->prophesize(TestSpyInterface::class)]);
        BuiltinFunctionMocker::$spies['getcwd']
            ->report()
            ->shouldBeCalledOnce()
            ->willThrow($previous);
        $translatableFactory = new TestTranslatableFactory();
        $sut = new SymfonyProcessFactory($translatableFactory);

        $message = sprintf('Failed to create process: %s', $previousMessage);
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->create(['arbitrary', 'command']);
        }, LogicException::class, $message);
    }
}
