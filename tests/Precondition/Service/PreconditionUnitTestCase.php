<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Environment\Service\EnvironmentInterface;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\PreconditionInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPathList;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

abstract class PreconditionUnitTestCase extends TestCase
{
    // Override in subclasses.
    protected const NAME = 'NAME';
    protected const DESCRIPTION = 'DESCRIPTION';
    protected const FULFILLED_STATUS_MESSAGE = 'FULFILLED_STATUS_MESSAGE';

    // Multiply expected calls to prophecies to account for multiple calls to ::isFulfilled()
    // and assertIsFulfilled() in ::doTestFulfilled() and ::doTestUnfulfilled(), respectively.
    protected const EXPECTED_CALLS_MULTIPLE = 3;

    protected EnvironmentInterface|ObjectProphecy $environment;
    protected PathListInterface $exclusions;

    protected function setUp(): void
    {
        $this->environment = $this->prophesize(EnvironmentInterface::class);
        $this->environment
            ->isWindows()
            ->willReturn(false);
        $this->environment
            ->setTimeLimit(Argument::any())
            ->willReturn(true);
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

        // It may seem silly to duplicate static string values EXACTLY from their corresponding SUTs, but it
        // makes sense to prevent changes to them since they are subject to our backward compatibility promise.
        self::assertSame(static::NAME, $sut->getName()->trans(), 'Got correct name.');
        self::assertSame(static::DESCRIPTION, $sut->getDescription()->trans(), 'Got correct description.');

        self::assertIsArray($sut->getLeaves());
    }

    protected function doTestFulfilled(
        string $expectedStatusMessage,
        ?PathInterface $activeDirPath = null,
        ?PathInterface $stagingDirPath = null,
        int $timeout = ProcessInterface::DEFAULT_TIMEOUT,
    ): void {
        $activeDirPath ??= PathHelper::activeDirPath();
        $stagingDirPath ??= PathHelper::stagingDirPath();

        $this->environment
            ->setTimeLimit($timeout)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout);
        $actualStatusMessage = $sut->getStatusMessage($activeDirPath, $stagingDirPath, $this->exclusions, $timeout);
        $sut->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout);

        self::assertTrue($isFulfilled);
        self::assertTranslatableMessage(static::FULFILLED_STATUS_MESSAGE, $actualStatusMessage, 'Got correct fulfilled status message.');
    }

    protected function doTestUnfulfilled(
        string $expectedStatusMessage,
        ?string $previousException = null,
        ?PathInterface $activeDirPath = null,
        ?PathInterface $stagingDirPath = null,
        int $timeout = ProcessInterface::DEFAULT_TIMEOUT,
    ): void {
        $activeDirPath ??= PathHelper::activeDirPath();
        $stagingDirPath ??= PathHelper::stagingDirPath();
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut, $activeDirPath, $stagingDirPath, $timeout): void {
            $sut->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout);
        }, PreconditionException::class, $expectedStatusMessage, null, $previousException);
    }
}
