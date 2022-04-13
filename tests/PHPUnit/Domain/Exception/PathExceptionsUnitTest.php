<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Exception;

use PhpTuf\ComposerStager\Domain\Exception\PathException;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;

final class PathExceptionsUnitTest extends TestCase
{
    /**
     * @covers \PhpTuf\ComposerStager\Domain\Exception\PathException
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality($args, $path, $expectedPathMessage): void
    {
        $pathException = new PathException(...$args);

        self::assertSame($expectedPathMessage, $pathException->getMessage());
    }

    public function providerBasicFunctionality(): array
    {
        return [
            // Defaults.
            [
                'args' => ['/one'],
                'path' => '/one',
                'expectedPathMessage' => '',
                'expectedDirectoryNotFoundMessage' => 'No such directory: "/one"',
            ],
            // Completely override message.
            [
                'args' => ['/two', 'Example message'],
                'path' => '/two',
                'expectedPathMessage' => 'Example message',
                'expectedDirectoryNotFound' => 'Example message',
            ],
            // Override message with path substitution.
            [
                'args' => ['/three/four', 'Example message: "%s"'],
                'path' => '/three/four',
                'expectedPathMessage' => 'Example message: "/three/four"',
            ],
        ];
    }
}
