<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Exception;

use PhpTuf\ComposerStager\Domain\Exception\FileNotFoundException;
use PhpTuf\ComposerStager\Domain\Exception\PathException;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;

final class PathExceptionsUnitTest extends TestCase
{
    /**
     * @covers \PhpTuf\ComposerStager\Domain\Exception\PathException
     * @covers \PhpTuf\ComposerStager\Domain\Exception\FileNotFoundException
     * @covers \PhpTuf\ComposerStager\Domain\Exception\PathException
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality(
        $args,
        $path,
        $expectedPathMessage,
        $expectedDirectoryAlreadyExistsMessage,
        $expectedDirectoryNotFoundMessage,
        $expectedDirectoryNotWritableMessage,
        $expectedFileNotFoundMessage
    ): void {
        $pathException = new PathException(...$args);
        $fileNotFoundException = new FileNotFoundException(...$args);

        self::assertSame($expectedPathMessage, $pathException->getMessage());
        self::assertSame($expectedFileNotFoundMessage, $fileNotFoundException->getMessage());
    }

    public function providerBasicFunctionality(): array
    {
        return [
            // Defaults.
            [
                'args' => ['/one'],
                'path' => '/one',
                'expectedPathMessage' => '',
                'expectedDirectoryAlreadyExistsMessage' => 'Directory already exists: "/one"',
                'expectedDirectoryNotFoundMessage' => 'No such directory: "/one"',
                'expectedDirectoryNotWritableMessage' => 'Directory not writable: "/one"',
                'expectedFileNotFoundMessage' => 'No such file: "/one"',
            ],
            // Completely override message.
            [
                'args' => ['/two', 'Example message'],
                'path' => '/two',
                'expectedPathMessage' => 'Example message',
                'expectedDirectoryAlreadyExistsMessage' => 'Example message',
                'expectedDirectoryNotFound' => 'Example message',
                'expectedDirectoryNotWritableMessage' => 'Example message',
                'expectedFileNotFound' => 'Example message',
            ],
            // Override message with path substitution.
            [
                'args' => ['/three/four', 'Example message: "%s"'],
                'path' => '/three/four',
                'expectedDirectoryAlreadyExistsMessage' => 'Example message: "/three/four"',
                'expectedPathMessage' => 'Example message: "/three/four"',
                'expectedDirectoryNotFound' => 'Example message: "/three/four"',
                'expectedDirectoryNotWritableMessage' => 'Example message: "/three/four"',
                'expectedFileNotFound' => 'Example message: "/three/four"',
            ],
        ];
    }
}
