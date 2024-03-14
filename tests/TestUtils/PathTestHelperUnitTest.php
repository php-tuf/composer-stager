<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

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

    public function providerFixSeparatorsMultiple(): array
    {
        return [
            'Empty arrays' => [
                'paths' => [],
                'expected' => [],
            ],
            'Single simple path' => [
                'paths' => ['simple'],
                'expected' => ['simple'],
            ],
            'Single path with depth' => [
                'paths' => ['one/two'],
                'expected' => ['one/two'],
            ],
            'Windows directory separators' => [
                'paths' => ['one\\two\\three'],
                'expected' => ['one/two/three'],
            ],
            'Multiple paths with mixed directory separators' => [
                'paths' => ['one/two\\three'],
                'expected' => ['one/two/three'],
            ],
        ];
    }
}
