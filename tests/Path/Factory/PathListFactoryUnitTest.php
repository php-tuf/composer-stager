<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Path\Factory;

use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathListFactory;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper;

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
                'expected' => PathTestHelper::createPathList(),
            ],
            'One path' => [
                'paths' => ['one'],
                'expected' => PathTestHelper::createPathList('one'),
            ],
            'Two paths' => [
                'paths' => ['one', 'two/two'],
                'expected' => PathTestHelper::createPathList('one', 'two/two'),
            ],
        ];
    }
}
