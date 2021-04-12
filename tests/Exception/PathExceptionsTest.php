<?php

namespace PhpTuf\ComposerStager\Tests\Exception;

use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\DirectoryNotWritableException;
use PhpTuf\ComposerStager\Exception\PathException;
use PHPUnit\Framework\TestCase;

class PathExceptionsTest extends TestCase
{
    /**
     * @covers \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     * @covers \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
     * @covers \PhpTuf\ComposerStager\Exception\PathException
     *
     * @dataProvider provider
     */
    public function test(
        $args,
        $path,
        $expectedPathMessage,
        $expectedNotFoundMessage,
        $expectedNotWritableMessage
    ): void {
        $pathException = new PathException(...$args);
        $notFoundException = new DirectoryNotFoundException(...$args);
        $notWritableException = new DirectoryNotWritableException(...$args);

        self::assertSame($expectedPathMessage, $pathException->getMessage());
        self::assertSame($expectedNotFoundMessage, $notFoundException->getMessage());
        self::assertSame($expectedNotWritableMessage, $notWritableException->getMessage());
        self::assertSame($path, $pathException->getPath());
    }

    public function provider(): array
    {
        return [
            // Defaults.
            [
                'args' => ['/lorem'],
                'path' => '/lorem',
                'expected_path_message' => '',
                'expected_not_found_message' => 'No such directory: "/lorem"',
                'expected_not_writable_message' => 'Directory not writable: "/lorem"',
            ],
            // Completely override message.
            [
                'args' => ['/ipsum', 'Lorem ipsum'],
                'path' => '/ipsum',
                'expected_path_message' => 'Lorem ipsum',
                'expected_not_found_message' => 'Lorem ipsum',
                'expected_not_writable_message' => 'Lorem ipsum',
            ],
            // Override message with path substitution.
            [
                'args' => ['/dolor/sit', 'Lorem ipsum: "%s"'],
                'path' => '/dolor/sit',
                'expected_path_message' => 'Lorem ipsum: "/dolor/sit"',
                'expected_not_found_message' => 'Lorem ipsum: "/dolor/sit"',
                'expected_not_writable_message' => 'Lorem ipsum: "/dolor/sit"',
            ],
        ];
    }
}
