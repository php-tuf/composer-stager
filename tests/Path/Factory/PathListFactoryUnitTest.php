<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Path\Factory;

use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathListFactory;
use PhpTuf\ComposerStager\Internal\Path\Value\PathList;
use PhpTuf\ComposerStager\Tests\TestCase;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Path\Factory\PathListFactory */
final class PathListFactoryUnitTest extends TestCase
{
    /**
     * @covers ::create
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality(array $paths, PathListInterface $expected): void
    {
        $sut = new PathListFactory();

        $actual = $sut->create(...$paths);

        self::assertEquals($expected, $actual, 'Returned correct path list object.');
    }

    public function providerBasicFunctionality(): array
    {
        return [
            'No paths' => [
                'paths' => [],
                'expected' => new PathList(),
            ],
            'One path' => [
                'paths' => ['one'],
                'expected' => new PathList('one'),
            ],
            'Two paths' => [
                'paths' => ['one', 'two/two'],
                'expected' => new PathList('one', 'two/two'),
            ],
        ];
    }
}
