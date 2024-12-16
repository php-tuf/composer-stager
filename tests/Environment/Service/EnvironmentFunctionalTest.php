<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Environment\Service;

use PhpTuf\ComposerStager\Internal\Environment\Service\Environment;
use PhpTuf\ComposerStager\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Environment::class)]
final class EnvironmentFunctionalTest extends TestCase
{
    private function createSut(): Environment
    {
        return new Environment();
    }

    #[DataProvider('providerSetTimeLimit')]
    public function testSetTimeLimit(int $timeout): void
    {
        $sut = $this->createSut();

        $result = $sut->setTimeLimit($timeout);

        self::assertSame((string) $timeout, ini_get('max_execution_time'), 'Correctly set timeout.');
        // At the time of this writing, `set_time_limit()` ALWAYS return false with XDebug
        // enabled. At least test that the SUT returns the same thing as a direct call.
        self::assertSame($result, set_time_limit($timeout), 'Returned same result as set_time_limit() called directly.');
    }

    public static function providerSetTimeLimit(): array
    {
        return [
            'Positive number' => [30],
            'Zero' => [0],
            'Negative number' => [-30],
        ];
    }
}
