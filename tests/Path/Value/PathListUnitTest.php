<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Path\Value;

use PhpTuf\ComposerStager\API\Path\Value\PathList;
use PhpTuf\ComposerStager\Tests\TestCase;

/** @coversDefaultClass \PhpTuf\ComposerStager\API\Path\Value\PathList */
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

        self::assertEquals($given, $sut->getAll(), 'Correctly set paths via constructor and got them.');

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
        ];
    }
}
