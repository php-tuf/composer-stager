<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Environment\Service;

use PhpTuf\ComposerStager\Internal\Environment\Service\Environment;
use PhpTuf\ComposerStager\Tests\TestCase;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Environment\Service\Environment */
final class EnvironmentFunctionalTest extends TestCase
{
    public function createSut(): Environment
    {
        return new Environment();
    }

    /**
     * @covers ::setTimeLimit
     *
     * @dataProvider providerSetTimeLimit
     */
    public function testSetTimeLimit(int $timeout): void
    {
        $sut = $this->createSut();

        $result = $sut->setTimeLimit($timeout);

        self::assertSame((string) $timeout, ini_get('max_execution_time'), 'Correctly set timeout.');
        // At the time of this writing, `set_time_limit()` ALWAYS return false with XDebug
        // enabled. At least test that the SUT returns the same thing as a direct call.
        self::assertSame($result, set_time_limit($timeout), 'Returned same result as set_time_limit() called directly.');
    }

    public function providerSetTimeLimit(): array
    {
        return [
            [-30],
            [-5],
            [0],
            [5],
            [30],
        ];
    }
}
