<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Precondition\Service\ActiveDirExistsInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\ActiveDirIsWritableInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveDirIsReady;
use PHPUnit\Framework\Attributes\CoversClass;
use Prophecy\Prophecy\ObjectProphecy;

#[CoversClass(ActiveDirIsReady::class)]
final class ActiveDirIsReadyUnitTest extends PreconditionUnitTestCase
{
    protected const NAME = 'Active directory is ready';
    protected const DESCRIPTION = 'The preconditions for using the active directory.';
    protected const FULFILLED_STATUS_MESSAGE = 'The active directory is ready to use.';

    private ActiveDirExistsInterface|ObjectProphecy $activeDirExists;
    private ActiveDirIsWritableInterface|ObjectProphecy $activeDirIsWritable;

    protected function setUp(): void
    {
        $this->activeDirExists = $this->prophesize(ActiveDirExistsInterface::class);
        $this->activeDirExists
            ->getLeaves()
            ->willReturn([$this->activeDirExists]);
        $this->activeDirIsWritable = $this->prophesize(ActiveDirIsWritableInterface::class);
        $this->activeDirIsWritable
            ->getLeaves()
            ->willReturn([$this->activeDirIsWritable]);

        parent::setUp();
    }

    protected function createSut(): ActiveDirIsReady
    {
        $environment = $this->environment->reveal();
        $stagingDirExists = $this->activeDirExists->reveal();
        $stagingDirIsWritable = $this->activeDirIsWritable->reveal();
        $translatableFactory = self::createTranslatableFactory();

        return new ActiveDirIsReady($environment, $stagingDirExists, $stagingDirIsWritable, $translatableFactory);
    }

    public function testFulfilled(): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();
        $timeout = 42;

        $this->activeDirExists
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->activeDirIsWritable
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);

        $this->doTestFulfilled(self::FULFILLED_STATUS_MESSAGE, $activeDirPath, $stagingDirPath, $timeout);
    }

    public function testUnfulfilled(): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();
        $timeout = 42;

        $message = __METHOD__;
        $previous = self::createTestPreconditionException($message);
        $this->activeDirExists
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout)
            ->willThrow($previous);

        $this->doTestUnfulfilled($message, null, $activeDirPath, $stagingDirPath, $timeout);
    }
}
