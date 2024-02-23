<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirExists;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirExists
 *
 * @covers ::__construct
 * @covers ::doAssertIsFulfilled
 * @covers \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition::getStatusMessage
 */
final class StagingDirExistsUnitTest extends PreconditionUnitTestCase
{
    protected const NAME = 'Staging directory exists';
    protected const DESCRIPTION = 'The staging directory must exist before any operations can be performed.';
    protected const FULFILLED_STATUS_MESSAGE = 'The staging directory exists.';

    private FilesystemInterface|ObjectProphecy $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(FilesystemInterface::class);

        parent::setUp();
    }

    protected function createSut(): StagingDirExists
    {
        $environment = $this->environment->reveal();
        $filesystem = $this->filesystem->reveal();
        $translatableFactory = self::createTranslatableFactory();

        return new StagingDirExists($environment, $filesystem, $translatableFactory);
    }

    /**
     * @covers ::doAssertIsFulfilled
     * @covers ::getFulfilledStatusMessage
     */
    public function testFulfilled(): void
    {
        $this->filesystem
            ->fileExists(self::stagingDirPath())
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE)
            ->willReturn(true);

        $this->doTestFulfilled(self::FULFILLED_STATUS_MESSAGE);
    }

    /** @covers ::doAssertIsFulfilled */
    public function testUnfulfilled(): void
    {
        $message = 'The staging directory does not exist.';
        $this->filesystem
            ->fileExists(self::stagingDirPath())
            ->willReturn(false);

        $this->doTestUnfulfilled($message);
    }
}
