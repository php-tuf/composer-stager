<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Value\PathAggregate;

use PhpTuf\ComposerStager\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Infrastructure\Aggregate\PathAggregate\PathAggregate;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Aggregate\PathAggregate\PathAggregate
 * @uses \PhpTuf\ComposerStager\Infrastructure\Aggregate\PathAggregate\PathAggregate::__construct
 */
class PathAggregateUnitTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::assertValidInput
     * @covers ::getAll
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality($paths): void
    {
        $sut = new PathAggregate($paths);

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
                    PathFactory::create('one'),
                    PathFactory::create('two'),
                ],
            ],
        ];
    }

    /**
     * @covers ::assertValidInput
     *
     * @dataProvider providerInvalidInput
     */
    public function testInvalidInput($paths): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Paths must implement/');

        new PathAggregate($paths);
    }

    public function providerInvalidInput(): array
    {
        return [
            'String value' => [
                'paths' => ['one'],
            ],
            'Null value' => [
                'paths' => [null],
            ],
            'Array value' => [
                'paths' => [[]],
            ],
            'Invalid value among valid ones' => [
                'paths' => [
                    PathFactory::create('one'),
                    'two',
                    PathFactory::create('three'),
                ],
            ],
        ];
    }
}
