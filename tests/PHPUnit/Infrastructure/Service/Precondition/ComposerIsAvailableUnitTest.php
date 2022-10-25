<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\LogicException;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinderInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ComposerIsAvailable;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ComposerIsAvailable
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 *
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 * @property \PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinderInterface|\Prophecy\Prophecy\ObjectProphecy $executableFinder
 */
final class ComposerIsAvailableUnitTest extends PreconditionTestCase
{
    protected function setUp(): void
    {
        $this->executableFinder = $this->prophesize(ExecutableFinderInterface::class);

        parent::setUp();
    }

    protected function createSut(): ComposerIsAvailable
    {
        $executableFinder = $this->executableFinder->reveal();

        return new ComposerIsAvailable($executableFinder);
    }

    public function testFulfilled(): void
    {
        // Double expectations: once for ::isFulfilled() and once for ::assertIsFulfilled().
        $this->executableFinder
            ->find('composer')
            ->shouldBeCalledTimes(2)
            ->willReturn('/usr/local/bin/composer');

        parent::testFulfilled();
    }

    public function testUnfulfilled(): void
    {
        // Double expectations: once for ::isFulfilled() and once for ::assertIsFulfilled().
        $this->executableFinder
            ->find('composer')
            ->shouldBeCalledTimes(2)
            ->willThrow(LogicException::class);

        parent::testUnfulfilled();
    }
}
