<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\PreconditionInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPathList;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableExceptionMessage;

abstract class PreconditionTestCase extends TestCase
{
    // Multiply expected calls to prophecies to account for multiple calls to ::isFulfilled()
    // and assertIsFulfilled() in ::doTestFulfilled() and ::doTestUnfulfilled(), respectively.
    protected const EXPECTED_CALLS_MULTIPLE = 3;

    protected function setUp(): void
    {
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

    protected function doTestFulfilled(
        string $expectedStatusMessage,
        ?PathInterface $activeDirPath = null,
        ?PathInterface $stagingDirPath = null,
    ): void {
        $activeDirPath ??= PathHelper::activeDirPath();
        $stagingDirPath ??= PathHelper::stagingDirPath();
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDirPath, $stagingDirPath, $this->exclusions);
        $actualStatusMessage = $sut->getStatusMessage($activeDirPath, $stagingDirPath, $this->exclusions);
        $sut->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions);

        self::assertTrue($isFulfilled);
        self::assertTranslatableMessage($expectedStatusMessage, $actualStatusMessage, 'Got correct status message.');
    }

    protected function doTestUnfulfilled(
        TranslatableInterface|string $expectedStatusMessage,
        ?string $previousException = null,
        ?PathInterface $activeDirPath = null,
        ?PathInterface $stagingDirPath = null,
    ): void {
        if (is_string($expectedStatusMessage)) {
            $expectedStatusMessage = new TestTranslatableExceptionMessage($expectedStatusMessage);
        }

        $activeDirPath ??= PathHelper::activeDirPath();
        $stagingDirPath ??= PathHelper::stagingDirPath();
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut, $activeDirPath, $stagingDirPath): void {
            $sut->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions);
        }, PreconditionException::class, $expectedStatusMessage, $previousException);
    }
}
