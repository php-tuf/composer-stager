<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Domain\Exception;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface;
use PhpTuf\ComposerStager\Tests\TestCase;

final class PreconditionExceptionUnitTest extends TestCase
{
    /** @covers \PhpTuf\ComposerStager\Domain\Exception\PreconditionException */
    public function testBasicFunctionality(): void
    {
        /** @var \PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface|\Prophecy\Prophecy\ObjectProphecy $precondition */
        $precondition = $this->prophesize(PreconditionInterface::class);
        $precondition = $precondition->reveal();

        $exception = new PreconditionException($precondition);

        self::assertSame($precondition, $exception->getPrecondition());
    }
}
