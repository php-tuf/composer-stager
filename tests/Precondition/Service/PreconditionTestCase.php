<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Precondition\Service\PreconditionInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPath;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPathList;
use PhpTuf\ComposerStager\Tests\TestCase;

abstract class PreconditionTestCase extends TestCase
{
    // Multiply expected calls to prophecies to account for multiple calls to ::isFulfilled()
    // and assertIsFulfilled() in ::doTestFulfilled() and ::doTestUnfulfilled(), respectively.
    protected const EXPECTED_CALLS_MULTIPLE = 3;

    protected function setUp(): void
    {
        $this->activeDir = new TestPath(self::ACTIVE_DIR_RELATIVE);
        $this->stagingDir = new TestPath(self::STAGING_DIR_RELATIVE);
        $this->exclusions = new TestPathList();
    }

    abstract protected function createSut(): PreconditionInterface;

    /**
     * @covers ::__construct
     * @covers ::getDescription
     * @covers ::getLeaves
     * @covers ::getName
     */
    public function testGetters(): void
    {
        $sut = $this->createSut();

        self::assertInstanceOf(TranslatableInterface::class, $sut->getName());
        self::assertInstanceOf(TranslatableInterface::class, $sut->getDescription());
        self::assertIsArray($sut->getLeaves());
    }

    protected function doTestFulfilled(string $expectedStatusMessage): void
    {
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir, $this->exclusions);
        $actualStatusMessage = $sut->getStatusMessage($this->activeDir, $this->stagingDir, $this->exclusions);
        $sut->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions);

        self::assertTrue($isFulfilled);
        self::assertTranslatableMessage($expectedStatusMessage, $actualStatusMessage, 'Got correct status message.');
    }

    protected function doTestUnfulfilled(
        TranslatableInterface $expectedStatusMessage,
        ?string $previousException = null,
    ): void {
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut) {
            $sut->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions);
        }, PreconditionException::class, $expectedStatusMessage, $previousException);
    }
}
