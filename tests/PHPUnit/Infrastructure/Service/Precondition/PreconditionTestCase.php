<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Value\PathList\TestPathList;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;

/**
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 * @property \PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface $exclusions
 */
abstract class PreconditionTestCase extends TestCase
{
    // Multiply expected calls to prophecies to account for multiple calls to ::isFulfilled()
    // and assertIsFulfilled() in ::doTestFulfilled() and ::doTestUnfulfilled(), respectively.
    protected const EXPECTED_CALLS_MULTIPLE = 3;

    protected function setUp(): void
    {
        $this->activeDir = $this->prophesize(PathInterface::class);
        $this->activeDir
            ->resolve()
            ->willReturn(self::ACTIVE_DIR);
        $this->stagingDir = $this->prophesize(PathInterface::class);
        $this->stagingDir
            ->resolve()
            ->willReturn(self::STAGING_DIR);
        $this->exclusions = new TestPathList();
    }

    abstract protected function createSut(): PreconditionInterface;

    protected function doTestFulfilled(string $expectedStatusMessage): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDir, $stagingDir, $this->exclusions);
        $actualStatusMessage = $sut->getStatusMessage($activeDir, $stagingDir, $this->exclusions);
        $sut->assertIsFulfilled($activeDir, $stagingDir, $this->exclusions);

        self::assertTrue($isFulfilled);
        self::assertSame($expectedStatusMessage, $actualStatusMessage, 'Get correct status message.');
    }

    protected function doTestUnfulfilled(string $expectedStatusMessage): void
    {
        $this->expectException(PreconditionException::class);

        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDir, $stagingDir, $this->exclusions);
        $actualStatusMessage = $sut->getStatusMessage($activeDir, $stagingDir, $this->exclusions);

        self::assertFalse($isFulfilled);
        self::assertSame($expectedStatusMessage, $actualStatusMessage, 'Get correct status message.');

        // This is called last so as not to throw the exception until all other
        // assertions have been made.
        $sut->assertIsFulfilled($activeDir, $stagingDir, $this->exclusions);
    }
}
