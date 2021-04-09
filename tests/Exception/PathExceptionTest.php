<?php

namespace PhpTuf\ComposerStager\Tests\Exception;

use PhpTuf\ComposerStager\Exception\PathException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Exception\PathException
 */
class PathExceptionTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getPath
     *
     * @dataProvider provider
     */
    public function test($path): void
    {
        $sut = new PathException($path);

        self::assertSame($path, $sut->getPath(), 'Handled path argument');
    }

    public function provider(): array
    {
        return [
            ['/lorem'],
            ['/ipsum'],
        ];
    }
}
