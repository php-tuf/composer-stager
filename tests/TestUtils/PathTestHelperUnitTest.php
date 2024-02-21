<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

use Generator;
use PhpTuf\ComposerStager\Tests\TestCase;

/** @coversNothing */
final class PathTestHelperUnitTest extends TestCase
{
    /** @dataProvider providerFixSeparatorsMultiple */
    public function testFixSeparatorsMultiple(array $paths, array $expected): void
    {
        PathTestHelper::fixSeparatorsMultiple(...$paths);

        self::assertSame($expected, $paths);
    }

    public function providerFixSeparatorsMultiple(): Generator
    {
        yield 'Empty arrays' => [
            'paths' => [],
            'expected' => [],
        ];

        yield 'Single simple path' => [
            'paths' => ['simple'],
            'expected' => ['simple'],
        ];

        if (EnvironmentTestHelper::isWindows()) {
            yield 'Single path with depth' => [
                'paths' => ['one/two'],
                'expected' => ['one\\two'],
            ];

            yield 'Multiple paths with mixed directory separators' => [
                'paths' => ['one/two\\three'],
                'expected' => ['one\\two\\three'],
            ];
        } else {
            yield 'Single path with depth' => [
                'paths' => ['one/two'],
                'expected' => ['one/two'],
            ];

            yield 'Multiple paths with mixed directory separators' => [
                'paths' => ['one/two\\three'],
                'expected' => ['one/two/three'],
            ];
        }
    }
}
