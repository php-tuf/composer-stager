<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Environment\Service;

use PhpTuf\ComposerStager\Internal\Environment\Service\Environment;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestDoubles\TestSpyInterface;
use PhpTuf\ComposerStager\Tests\TestUtils\BuiltinFunctionMocker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Prophecy\Argument;

#[CoversClass(Environment::class)]
final class EnvironmentUnitTest extends TestCase
{
    private function createSut(): Environment
    {
        return new Environment();
    }

    public function testIsWindows(): void
    {
        $isWindowsDirectorySeparator = DIRECTORY_SEPARATOR === '\\';
        $sut = $this->createSut();

        self::assertEquals($isWindowsDirectorySeparator, $sut->isWindows());
    }

    #[DataProvider('providerSetTimeLimitFunctionExists')]
    #[RunInSeparateProcess]
    public function testSetTimeLimitFunctionExists(int $seconds, bool $setTimeLimitReturn): void
    {
        BuiltinFunctionMocker::mock(['set_time_limit' => $this->prophesize(TestSpyInterface::class)]);
        BuiltinFunctionMocker::$spies['set_time_limit']
            ->report($seconds)
            ->shouldBeCalledOnce()
            ->willReturn($setTimeLimitReturn);
        $sut = $this->createSut();

        $actualReturn = $sut->setTimeLimit($seconds);

        self::assertSame($actualReturn, $setTimeLimitReturn, 'Passed through the return value from set_time_limit().');
    }

    public static function providerSetTimeLimitFunctionExists(): array
    {
        return [
            [
                'seconds' => 10,
                'setTimeLimitReturn' => true,
            ],
            [
                'seconds' => 100,
                'setTimeLimitReturn' => false,
            ],
        ];
    }

    #[RunInSeparateProcess]
    public function testSetTimeLimitFunctionDoesNotExist(): void
    {
        BuiltinFunctionMocker::mock([
            'function_exists' => $this->prophesize(TestSpyInterface::class),
            'set_time_limit' => $this->prophesize(TestSpyInterface::class),
        ]);
        BuiltinFunctionMocker::$spies['function_exists']
            ->report('set_time_limit')
            ->shouldBeCalledOnce()
            ->willReturn(false);
        BuiltinFunctionMocker::$spies['set_time_limit']
            ->report(Argument::any())
            ->shouldNotBeCalled();
        $sut = $this->createSut();

        $actualReturn = $sut->setTimeLimit(42);

        self::assertFalse($actualReturn);
    }
}
