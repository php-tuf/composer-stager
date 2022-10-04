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
     * @covers ::assertValidInput
     * @covers ::getAll
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality(array $paths): void
    {
        $sut = new PathList($paths);

        self::assertEquals($paths, $sut->getAll(), 'Got correct value via explicit method call.');
    }

    public function providerBasicFunctionality(): array
    {
        return [
            'Empty array' => [
                'paths' => [],
            ],
            'With values' => [
                'paths' => [
                    'one',
                    'two',
                ],
            ],
        ];
    }

    /**
     * @covers ::assertValidInput
     *
     * @dataProvider providerInvalidInput
     */
    public function testInvalidInput(array $paths, string $givenType): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Paths must be strings. Given {$givenType}");

        new PathList($paths);
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
