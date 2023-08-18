<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Path\Factory;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory;
use PhpTuf\ComposerStager\Internal\Path\Value\Path;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPath;
use PhpTuf\ComposerStager\Tests\TestCase;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory */
final class PathFactoryUnitTest extends TestCase
{
    /**
     * @covers ::create
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality(
        string $string,
        PathInterface $basePath,
        PathInterface $expected,
        PathInterface $expectedWithBaseDir,
    ): void {
        $sut = new PathFactory();

        $actual = $sut->create($string);
        $actualWithBaseDir = $sut->create($string, $basePath);

        self::assertEquals($expected, $actual, 'Returned correct path object.');
        self::assertEquals($expectedWithBaseDir, $actualWithBaseDir, 'Returned correct path object given a $basePath argument.');
    }

    public function providerBasicFunctionality(): array
    {
        return [
            'Simple values' => [
                'string' => 'test.txt',
                'basePath' => new TestPath(),
                'expected' => new Path('test.txt'),
                'expectedWithBaseDir' => new Path('test.txt', new TestPath()),
            ],
        ];
    }
}
