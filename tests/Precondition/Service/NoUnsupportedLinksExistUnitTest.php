<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Precondition\Service\NoAbsoluteSymlinksExistInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\NoHardLinksExistInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\NoLinksExistOnWindowsInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\NoSymlinksPointOutsideTheCodebaseInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoUnsupportedLinksExist;
use PHPUnit\Framework\Attributes\CoversClass;
use Prophecy\Prophecy\ObjectProphecy;

#[CoversClass(NoUnsupportedLinksExist::class)]
final class NoUnsupportedLinksExistUnitTest extends PreconditionUnitTestCase
{
    protected const NAME = 'Unsupported links preconditions';
    protected const DESCRIPTION = 'Preconditions concerning unsupported links.';
    protected const FULFILLED_STATUS_MESSAGE = 'There are no unsupported links in the codebase.';

    private NoAbsoluteSymlinksExistInterface|ObjectProphecy $noAbsoluteSymlinksExist;
    private NoHardLinksExistInterface|ObjectProphecy $noHardLinksExist;
    private NoLinksExistOnWindowsInterface|ObjectProphecy $noLinksExistOnWindows;
    private NoSymlinksPointOutsideTheCodebaseInterface|ObjectProphecy $noSymlinksPointOutsideTheCodebase;

    protected function setUp(): void
    {
        $this->noAbsoluteSymlinksExist = $this->prophesize(NoAbsoluteSymlinksExistInterface::class);
        $this->noHardLinksExist = $this->prophesize(NoHardLinksExistInterface::class);
        $this->noLinksExistOnWindows = $this->prophesize(NoLinksExistOnWindowsInterface::class);
        $this->noSymlinksPointOutsideTheCodebase = $this->prophesize(NoSymlinksPointOutsideTheCodebaseInterface::class);
        $this->noAbsoluteSymlinksExist
            ->getLeaves()
            ->willReturn([$this->noAbsoluteSymlinksExist]);
        $this->noHardLinksExist
            ->getLeaves()
            ->willReturn([$this->noHardLinksExist]);
        $this->noLinksExistOnWindows
            ->getLeaves()
            ->willReturn([$this->noLinksExistOnWindows]);
        $this->noSymlinksPointOutsideTheCodebase
            ->getLeaves()
            ->willReturn([$this->noSymlinksPointOutsideTheCodebase]);

        parent::setUp();
    }

    protected function createSut(): NoUnsupportedLinksExist
    {
        $environment = $this->environment->reveal();
        $noAbsoluteSymlinksExist = $this->noAbsoluteSymlinksExist->reveal();
        $noHardLinksExist = $this->noHardLinksExist->reveal();
        $noLinksExistOnWindows = $this->noLinksExistOnWindows->reveal();
        $noSymlinksPointOutsideTheCodebase = $this->noSymlinksPointOutsideTheCodebase->reveal();
        $translatableFactory = self::createTranslatableFactory();

        return new NoUnsupportedLinksExist(
            $environment,
            $translatableFactory,
            $noAbsoluteSymlinksExist,
            $noHardLinksExist,
            $noLinksExistOnWindows,
            $noSymlinksPointOutsideTheCodebase,
        );
    }

    public function testFulfilled(): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();
        $timeout = 42;

        $this->noAbsoluteSymlinksExist
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->noHardLinksExist
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->noLinksExistOnWindows
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->noSymlinksPointOutsideTheCodebase
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);

        $this->doTestFulfilled(
            'There are no unsupported links in the codebase.',
            $activeDirPath,
            $stagingDirPath,
            $timeout,
        );
    }

    public function testUnfulfilled(): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();
        $timeout = 42;

        $message = __METHOD__;
        $previous = self::createTestPreconditionException($message);
        $this->noAbsoluteSymlinksExist
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout)
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableMessage($message, $sut->getStatusMessage(
            $activeDirPath,
            $stagingDirPath,
            $this->exclusions,
            $timeout,
        ));
        self::assertTranslatableException(function () use ($sut, $activeDirPath, $stagingDirPath, $timeout): void {
            $sut->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout);
        }, PreconditionException::class, $message);
    }
}
