<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Aggregate\PathAggregate;

use PhpTuf\ComposerStager\Domain\Aggregate\PathAggregate\NullPathAggregate;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;

/**
 * @covers \PhpTuf\ComposerStager\Domain\Aggregate\PathAggregate\NullPathAggregate
 */
class NullPathAggregateUnitTest extends TestCase
{
    public function testBasicFunctionality(): void
    {
        $sut = new NullPathAggregate();

        self::assertEquals([], $sut->getAll());
    }
}
