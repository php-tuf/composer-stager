<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Path\Value;

use PhpTuf\ComposerStager\Internal\Path\Value\PathList;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\EnvironmentTestHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(PathList::class)]
final class PathListUnitTest extends TestCase
{
    #[DataProvider('providerBasicFunctionality')]
    public function testBasicFunctionality(array $given, array $add, array $expected): void
    {
        $pathHelper = self::createPathHelper();
        $sut = new PathList($pathHelper, ...$given);

        $sut->add(...$add);

        self::assertEquals($expected, $sut->getAll(), 'Correctly added paths and got them.');
    }

    public static function providerBasicFunctionality(): array
    {
        $data = [
            'None given, none added' => [
                'given' => [],
                'add' => [],
                'expected' => [],
            ],
            'Some given, none added' => [
                'given' => [
                    'one',
                    'two',
                ],
                'add' => [],
                'expected' => [
                    'one',
                    'two',
                ],
            ],
            'None given, some added' => [
                'given' => [],
                'add' => [
                    'one',
                    'two',
                ],
                'expected' => [
                    'one',
                    'two',
                ],
            ],
            'Some given, some added' => [
                'given' => [
                    'one',
                    'two',
                ],
                'add' => [
                    'three',
                    'four',
                ],
                'expected' => [
                    'one',
                    'two',
                    'three',
                    'four',
                ],
            ],
        ];

        return array_merge($data, EnvironmentTestHelper::isWindows()
            ? self::providerBasicFunctionalityWindows()
            : self::providerBasicFunctionalityUnixLike());
    }

    private static function providerBasicFunctionalityWindows(): array
    {
        return [
            'Different directory separators' => [
                'given' => [
                    'one',
                    'two\\two',
                    'three/three/three',
                ],
                'add' => [
                    'four\\four/four\\four',
                    'five/five\\five/five\\five',
                ],
                'expected' => [
                    'one',
                    'two/two',
                    'three/three/three',
                    'four/four/four/four',
                    'five/five/five/five/five',
                ],
            ],
            'Complex paths' => [
                'given' => [
                    'one\\two',
                    'three/four/five',
                    'six\\seven/..\\eight/nine',
                ],
                'add' => ['nine\\ten\\..\\..\\eleven\\twelve'],
                'expected' => [
                    'one/two',
                    'three/four/five',
                    'six/eight/nine',
                    'eleven/twelve',
                ],
            ],
            'Duplicate paths' => [
                'given' => [
                    'one\\two',
                    'one\\two',
                    'three\\four\\five',
                ],
                'add' => [
                    'three\\four\\five',
                    'six\\seven\\eight\\nine',
                    'six\\seven\\eight\\nine',
                ],
                'expected' => [
                    'one/two',
                    'three/four/five',
                    'six/seven/eight/nine',
                ],
            ],
        ];
    }

    private static function providerBasicFunctionalityUnixLike(): array
    {
        return [
            'Different directory separators' => [
                'given' => [
                    'one',
                    'two/two',
                    'three\\three\\three',
                ],
                'add' => [
                    'four/four\\four/four',
                    'five\\five/five\\five/five',
                ],
                'expected' => [
                    'one',
                    'two/two',
                    'three/three/three',
                    'four/four/four/four',
                    'five/five/five/five/five',
                ],
            ],
            'Complex paths' => [
                'given' => [
                    'one/two',
                    'three\\four\\five',
                    'six/seven\\../eight\\nine',
                ],
                'add' => ['nine/ten/../../eleven/twelve'],
                'expected' => [
                    'one/two',
                    'three/four/five',
                    'six/eight/nine',
                    'eleven/twelve',
                ],
            ],
            'Duplicate paths' => [
                'given' => [
                    'one/two',
                    'one/two',
                    'three/four/five',
                ],
                'add' => [
                    'three/four/five',
                    'six/seven/eight/nine',
                    'six/seven/eight/nine',
                ],
                'expected' => [
                    'one/two',
                    'three/four/five',
                    'six/seven/eight/nine',
                ],
            ],
        ];
    }
}
