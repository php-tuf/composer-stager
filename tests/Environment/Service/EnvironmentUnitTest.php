<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Environment\Service;

use PhpTuf\ComposerStager\Internal\Environment\Service\Environment;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\TestSpyInterface;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Environment\Service\Environment */
final class EnvironmentUnitTest extends TestCase
{
    public static ObjectProphecy $functionExistsSpy;
    public static ObjectProphecy $setTimeLimitSpy;

    protected function setUp(): void
    {
        self::$functionExistsSpy = $this->prophesize(TestSpyInterface::class);
        self::$functionExistsSpy
            ->report(Argument::any())
            ->willReturn(true);
        self::$setTimeLimitSpy = $this->prophesize(TestSpyInterface::class);
        self::$setTimeLimitSpy
            ->report(Argument::any())
            ->willReturn(true);
    }

    public function createSut(): Environment
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
        $this->mockGlobalFunctions();
        self::$functionExistsSpy
            ->report('set_time_limit')
            ->shouldBeCalledOnce()
            ->willReturn(true);
        self::$setTimeLimitSpy
            ->report($seconds)
            ->shouldBeCalledOnce()
            ->willReturn($setTimeLimitReturn);
        $sut = $this->createSut();

        $actualReturn = $sut->setTimeLimit($seconds);

        self::assertSame($actualReturn, $setTimeLimitReturn, 'Passed through the return value from set_time_limit().');
    }

    /**
     * @covers ::setTimeLimit
     *
     * @runInSeparateProcess
     */
    public function testSetTimeLimitFunctionDoesNotExist(): void
    {
        $this->mockGlobalFunctions();
        self::$functionExistsSpy
            ->report('set_time_limit')
            ->shouldBeCalledOnce()
            ->willReturn(false);
        self::$setTimeLimitSpy
            ->report(Argument::any())
            ->shouldNotBeCalled();
        $sut = $this->createSut();

        $actualReturn = $sut->setTimeLimit(42);

        self::assertFalse($actualReturn);
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

    private function mockGlobalFunctions(): void
    {
        require_once __DIR__ . '/environment_unit_test_function_mocks.inc';
    }
}
