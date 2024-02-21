<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Path\Factory;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory;
use PhpTuf\ComposerStager\Internal\Path\Value\Path;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper;

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
        PathInterface $expected,
        PathInterface $basePath,
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
                'expected' => new Path('test.txt'),
                'basePath' => PathTestHelper::createPath('/var/www'),
                'expectedWithBaseDir' => new Path('test.txt', PathTestHelper::createPath('/var/www')),
            ],
        ];
    }
}
