<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Value\PathList;

use PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use stdClass;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList::__construct
 */
final class PathListUnitTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::add
     * @covers ::assertValidInput
     * @covers ::getAll
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality(array $given, array $add, array $expected): void
    {
        $sut = new PathList($given);

        self::assertEquals($given, $sut->getAll(), 'Correctly set paths via constructor and got them.');

        $sut->add($add);

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

    /**
     * @covers ::assertValidInput
     *
     * @uses \PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList::add
     *
     * @dataProvider providerInvalidInput
     */
    public function testConstructWithInvalidInput(array $paths, string $givenType): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Paths must be strings. Given {$givenType}");

        new PathList($paths);
    }

    /**
     * @covers ::add
     * @covers ::assertValidInput
     *
     * @dataProvider providerInvalidInput
     */
    public function testAddInvalidInput(array $paths, string $givenType): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Paths must be strings. Given {$givenType}");

        $sut = new PathList([]);

        $sut->add($paths);
    }

    public function providerInvalidInput(): array
    {
        return [
            'Null value' => [
                'paths' => [null],
                'givenType' => 'NULL',
            ],
            'Array value' => [
                'paths' => [[]],
                'givenType' => 'array',
            ],
            'Object value' => [
                'paths' => [new stdClass()],
                'givenType' => 'stdClass',
            ],
            'Invalid value among valid ones' => [
                'paths' => [
                    'one',
                    null,
                    'three',
                ],
                'givenType' => 'NULL',
            ],
        ];
    }
}
