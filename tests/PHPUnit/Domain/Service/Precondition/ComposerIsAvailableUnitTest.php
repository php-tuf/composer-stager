<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\LogicException;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinderInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ComposerIsAvailable;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ComposerIsAvailable
 *
 * @covers \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ComposerIsAvailable::__construct
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition
 *
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 * @property \PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinderInterface|\Prophecy\Prophecy\ObjectProphecy $executableFinder
 */
final class ComposerIsAvailableUnitTest extends TestCase
{
    protected function setUp(): void
    {
        $this->activeDir = $this->prophesize(PathInterface::class);
        $this->stagingDir = $this->prophesize(PathInterface::class);
        $this->executableFinder = $this->prophesize(ExecutableFinderInterface::class);
    }

    protected function createSut(): ComposerIsAvailable
    {
        $executableFinder = $this->executableFinder->reveal();

        return new ComposerIsAvailable($executableFinder);
    }

    /**
     * @covers ::__construct
     * @covers ::assertIsFulfilled
     * @covers ::isFulfilled
     */
    public function testIsFulfilled(): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->executableFinder
            ->find('composer')
            ->shouldBeCalledOnce()
            ->willReturn('/usr/local/bin/composer');
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDir, $stagingDir);

        self::assertTrue($isFulfilled);
    }

    /**
     * @covers ::__construct
     * @covers ::assertIsFulfilled
     * @covers ::isFulfilled
     */
    public function testIsUnfulfilled(): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->executableFinder
            ->find('composer')
            ->shouldBeCalledOnce()
            ->willThrow(LogicException::class);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDir, $stagingDir);

        self::assertFalse($isFulfilled);
    }
}
