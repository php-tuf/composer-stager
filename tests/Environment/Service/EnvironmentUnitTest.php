<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Environment\Service;

use PhpTuf\ComposerStager\Internal\Environment\Service\Environment;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestDoubles\TestSpyInterface;
use PhpTuf\ComposerStager\Tests\TestUtils\BuiltinFunctionMocker;
use Prophecy\Argument;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Environment\Service\Environment */
final class EnvironmentUnitTest extends TestCase
{
    private function createSut(): Environment
    {
        return new Environment();
    }

    /** @covers ::isWindows */
    public function testIsWindows(): void
    {
        $isWindowsDirectorySeparator = DIRECTORY_SEPARATOR === '\\';
        $sut = $this->createSut();

        self::assertEquals($isWindowsDirectorySeparator, $sut->isWindows());
    }

    /**
     * @covers ::setTimeLimit
     *
     * @dataProvider providerSetTimeLimitFunctionExists
     *
     * @runInSeparateProcess
     */
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

    public function providerSetTimeLimitFunctionExists(): array
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

    /**
     * @covers ::setTimeLimit
     *
     * @runInSeparateProcess
     */
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
