<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Factory\Path;

use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath;
use PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath
 */
final class PathFactoryUnitTest extends TestCase
{
    /**
     * It's difficult to meaningfully test this class because it is a static
     * factory, but it has an external dependency on a PHP constant that cannot
     * be mocked. The tests themselves must therefore be conditioned on the
     * external environment, which is obviously "cheating".
     *
     * @covers ::create
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality($directorySeparator, $instanceOf): void
    {
        $path = PathFactory::create($directorySeparator);

        /** @noinspection UnnecessaryAssertionInspection */
        self::assertInstanceOf($instanceOf, $path, 'Returned correct path object.');
    }

    public function providerBasicFunctionality(): array
    {
        if (self::isWindows()) {
            return [
                [
                    'string' => '\\',
                    'instanceOf' => WindowsPath::class,
                ],
            ];
        }

        return [
            [
                'string' => '/',
                'instanceOf' => UnixLikePath::class,
            ],
        ];
    }
}
