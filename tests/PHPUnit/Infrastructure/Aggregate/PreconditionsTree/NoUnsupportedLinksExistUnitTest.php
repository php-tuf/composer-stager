<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Aggregate\PreconditionsTree;

use PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\NoUnsupportedLinksExist;
use PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\Precondition\PreconditionTestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\NoUnsupportedLinksExist
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getUnfulfilledStatusMessage
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\AbstractPreconditionsTree
 */
final class NoUnsupportedLinksExistUnitTest extends PreconditionTestCase
{
    protected function createSut(): NoUnsupportedLinksExist
    {
        return new NoUnsupportedLinksExist();
    }

    public function testFulfilled(): void
    {
        $this->doTestFulfilled('There are no unsupported links in the codebase.');
    }

    public function testUnfulfilled(): void
    {
        // @todo Implement once the corresponding functionality is added.
        $this->expectNotToPerformAssertions();
    }
}
