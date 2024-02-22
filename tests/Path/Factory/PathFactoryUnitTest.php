<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Path\Factory;

use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory;
use PhpTuf\ComposerStager\Internal\Path\Service\PathHelper;
use PhpTuf\ComposerStager\Internal\Path\Value\Path;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory */
final class PathFactoryUnitTest extends TestCase
{
    private function createSut(): PathFactory
    {
        $pathHelper = PathTestHelper::createPathHelper();

        return new PathFactory($pathHelper);
    }

    /**
     * @covers ::__construct
     * @covers ::create
     */
    public function testBasicFunctionality(): void
    {
        $pathHelper = new PathHelper();
        $filename = 'test.txt';
        $basePath = new Path($pathHelper, '/var/www');

        $expected = new Path($pathHelper, $filename);
        $expectedWithBaseDir = new Path($pathHelper, $filename, $basePath);

        $sut = $this->createSut();

        $actual = $sut->create($filename);
        $actualWithBaseDir = $sut->create($filename, $basePath);

        self::assertEquals($expected, $actual, 'Returned correct path object.');
        self::assertEquals($expectedWithBaseDir, $actualWithBaseDir, 'Returned correct path object given a $basePath argument.');
    }
}
