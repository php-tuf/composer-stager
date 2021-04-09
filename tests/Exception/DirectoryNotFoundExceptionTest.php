<?php

namespace PhpTuf\ComposerStager\Tests\Exception;

use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 */
class DirectoryNotFoundExceptionTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getPath
     *
     * @dataProvider provider
     */
    public function test($args, $expectedMessage): void
    {
        $sut = new DirectoryNotFoundException(...$args);

        self::assertSame($expectedMessage, $sut->getMessage(), 'Handled message argument');
    }

    public function provider(): array
    {
        return [
            [
                'args' => ['/lorem'],
                'expected_message' => 'No such directory: "/lorem"',
            ],
            [
                'args' => ['/ipsum'],
                'expected_message' => 'No such directory: "/ipsum"',
            ],
            [
                'args' => ['/ipsum', 'Lorem ipsum'],
                'expected_message' => 'Lorem ipsum',
            ],
        ];
    }
}
