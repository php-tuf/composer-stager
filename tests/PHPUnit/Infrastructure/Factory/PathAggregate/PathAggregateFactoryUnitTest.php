<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Factory\PathAggregate;

use PhpTuf\ComposerStager\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Infrastructure\Factory\PathAggregate\PathAggregateFactory;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Factory\PathAggregate\PathAggregateFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Aggregate\PathAggregate\PathAggregate
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath
 */
class PathAggregateFactoryUnitTest extends TestCase
{
    /**
     * @covers ::create
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality(array $paths, array $expected): void
    {
        $aggregate = PathAggregateFactory::create($paths);

        self::assertEquals($expected, $aggregate->getAll());
    }

    public function providerBasicFunctionality(): array
    {
        return [
            [
                'paths' => [],
                'expected' => [],
            ],
            [
                'paths' => ['var/www'],
                'expected' => [PathFactory::create('var/www')],
            ],
            [
                'paths' => [
                    'var/one',
                    'var/two',
                ],
                'expected' => [
                    PathFactory::create('var/one'),
                    PathFactory::create('var/two'),
                ],
            ],
        ];
    }

    /**
     * @covers ::create
     *
     * @dataProvider providerInvalidInput
     */
    public function testInvalidInput($paths): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Paths must be strings/');

        PathAggregateFactory::create($paths);
    }

    public function providerInvalidInput(): array
    {
        return [
            'Null value' => [
                'paths' => [null],
            ],
            'Array value' => [
                'paths' => [[]],
            ],
            'Invalid value among valid ones' => [
                'paths' => [
                    'one',
                    null,
                    'two',
                ],
            ],
        ];
    }
}
