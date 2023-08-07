<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Path\Factory;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Internal\Host\Service\Host;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory;
use PhpTuf\ComposerStager\Internal\Path\Value\UnixLikePath;
use PhpTuf\ComposerStager\Internal\Path\Value\WindowsPath;
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
        $actual = PathFactory::create($string);
        $actualWithBaseDir = PathFactory::create($string, $basePath);

        self::assertEquals($expected, $actual, 'Returned correct path object.');
        self::assertEquals($expectedWithBaseDir, $actualWithBaseDir, 'Returned correct path object given a $basePath argument.');
    }

    public function providerBasicFunctionality(): array
    {
        // It's difficult to meaningfully test this class because it's a static
        // factory, but it has an external dependency on a PHP constant that cannot
        // be mocked. The tests themselves must therefore be conditioned on the
        // external environment (which is obviously "cheating").
        if (Host::isWindows()) {
            return [
                [
                    'string' => 'test.txt',
                    'baseDir' => new TestPath(),
                    'expected' => new WindowsPath('test.txt'),
                    'expectedWithBaseDir' => new WindowsPath('test.txt', new TestPath()),
                ],
            ];
        }

        return [
            [
                'string' => 'test.txt',
                'baseDir' => new TestPath(),
                'expected' => new UnixLikePath('test.txt'),
                'expectedWithBaseDir' => new UnixLikePath('test.txt', new TestPath()),
            ],
        ];
    }
}
