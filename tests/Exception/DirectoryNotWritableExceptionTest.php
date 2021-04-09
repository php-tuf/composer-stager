<?php

namespace PhpTuf\ComposerStager\Tests\Exception;

use PhpTuf\ComposerStager\Exception\DirectoryNotWritableException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 */
class DirectoryNotWritableExceptionTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getPath
     *
     * @dataProvider provider
     */
    public function test($args, $expectedMessage): void
    {
        $sut = new DirectoryNotWritableException(...$args);

        self::assertSame($expectedMessage, $sut->getMessage(), 'Handled message argument');
    }

    public function provider(): array
    {
        return [
            [
                'args' => ['/lorem'],
                'expected_message' => 'Directory not writable: "/lorem"',
            ],
            [
                'args' => ['/ipsum'],
                'expected_message' => 'Directory not writable: "/ipsum"',
            ],
            [
                'args' => ['/ipsum', 'Lorem ipsum'],
                'expected_message' => 'Lorem ipsum',
            ],
        ];
    }
}
