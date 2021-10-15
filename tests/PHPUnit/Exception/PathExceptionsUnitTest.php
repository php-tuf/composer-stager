<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Exception;

use PhpTuf\ComposerStager\Exception\DirectoryAlreadyExistsException;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\DirectoryNotWritableException;
use PhpTuf\ComposerStager\Exception\FileNotFoundException;
use PhpTuf\ComposerStager\Exception\PathException;
use PHPUnit\Framework\TestCase;

class PathExceptionsUnitTest extends TestCase
{
    /**
     * @covers \PhpTuf\ComposerStager\Exception\DirectoryAlreadyExistsException
     * @covers \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     * @covers \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
     * @covers \PhpTuf\ComposerStager\Exception\FileNotFoundException
     * @covers \PhpTuf\ComposerStager\Exception\PathException
     *
     * @dataProvider provider
     */
    public function test(
        $args,
        $path,
        $expectedPathMessage,
        $expectedDirectoryAlreadyExistsMessage,
        $expectedDirectoryNotFoundMessage,
        $expectedDirectoryNotWritableMessage,
        $expectedFileNotFoundMessage
    ): void {
        $pathException = new PathException(...$args);
        $directoryAlreadyExistsException = new DirectoryAlreadyExistsException(...$args);
        $directoryNotFoundException = new DirectoryNotFoundException(...$args);
        $directoryNotWritableException = new DirectoryNotWritableException(...$args);
        $fileNotFoundException = new FileNotFoundException(...$args);

        self::assertSame($expectedPathMessage, $pathException->getMessage());
        self::assertSame($path, $pathException->getPath());
        self::assertSame($expectedDirectoryAlreadyExistsMessage, $directoryAlreadyExistsException->getMessage());
        self::assertSame($expectedDirectoryNotFoundMessage, $directoryNotFoundException->getMessage());
        self::assertSame($expectedDirectoryNotWritableMessage, $directoryNotWritableException->getMessage());
        self::assertSame($expectedFileNotFoundMessage, $fileNotFoundException->getMessage());
    }

    public function provider(): array
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
                'args' => ['/two', 'Lorem ipsum'],
                'path' => '/two',
                'expectedPathMessage' => 'Lorem ipsum',
                'expectedDirectoryAlreadyExistsMessage' => 'Lorem ipsum',
                'expectedDirectoryNotFound' => 'Lorem ipsum',
                'expectedDirectoryNotWritableMessage' => 'Lorem ipsum',
                'expectedFileNotFound' => 'Lorem ipsum',
            ],
            // Override message with path substitution.
            [
                'args' => ['/three/four', 'Lorem ipsum: "%s"'],
                'path' => '/three/four',
                'expectedDirectoryAlreadyExistsMessage' => 'Lorem ipsum: "/three/four"',
                'expectedPathMessage' => 'Lorem ipsum: "/three/four"',
                'expectedDirectoryNotFound' => 'Lorem ipsum: "/three/four"',
                'expectedDirectoryNotWritableMessage' => 'Lorem ipsum: "/three/four"',
                'expectedFileNotFound' => 'Lorem ipsum: "/three/four"',
            ],
        ];
    }
}
