<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Path\Factory;

use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathListFactory;
use PhpTuf\ComposerStager\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(PathListFactory::class)]
final class PathListFactoryUnitTest extends TestCase
{
    #[DataProvider('providerBasicFunctionality')]
    public function testBasicFunctionality(array $paths, PathListInterface $expected): void
    {
        $pathHelper = self::createPathHelper();
        $sut = new PathListFactory($pathHelper);

        $actual = $sut->create(...$paths);

        self::assertEquals($expected, $actual, 'Returned correct path list object.');
    }

    public static function providerBasicFunctionality(): array
    {
        return [
            'No paths' => [
                'paths' => [],
                'expected' => self::createPathList(),
            ],
            'One path' => [
                'paths' => ['one'],
                'expected' => self::createPathList('one'),
            ],
            'Two paths' => [
                'paths' => ['one', 'two/two'],
                'expected' => self::createPathList('one', 'two/two'),
            ],
        ];
    }
}
