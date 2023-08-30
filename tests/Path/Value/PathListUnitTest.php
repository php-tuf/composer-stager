<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Path\Value;

use PhpTuf\ComposerStager\Internal\Path\Value\PathList;
use PhpTuf\ComposerStager\Tests\TestCase;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Path\Value\PathList */
final class PathListUnitTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::add
     * @covers ::getAll
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality(array $given, array $add, array $expected): void
    {
        $sut = new PathList(...$given);

        $sut->add(...$add);

        self::assertEquals($expected, $sut->getAll(), 'Correctly added paths and got them.');
    }

    public function providerBasicFunctionality(): array
    {
        return [
            'None given, none added' => [
                'paths' => [],
                'add' => [],
                'expected' => [],
            ],
            'Some given, none added' => [
                'paths' => [
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
                'paths' => [],
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
                'paths' => [
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
            'Different directory separators' => [
                'paths' => [
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
                'paths' => [
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
        ];
    }
}
