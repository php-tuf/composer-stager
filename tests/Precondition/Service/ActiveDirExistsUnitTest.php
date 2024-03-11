<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveDirExists;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveDirExists
 *
 * @covers ::__construct
 * @covers \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition::getStatusMessage
 */
final class ActiveDirExistsUnitTest extends PreconditionUnitTestCase
{
    protected const NAME = 'Active directory exists';
    protected const DESCRIPTION = 'There must be an active directory present before any operations can be performed.';
    protected const FULFILLED_STATUS_MESSAGE = 'The active directory exists.';

    private FilesystemInterface|ObjectProphecy $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->fileExists(self::activeDirPath())
            ->willReturn(true);
        $this->filesystem
            ->isDir(self::activeDirPath())
            ->willReturn(true);

        parent::setUp();
    }

    protected function createSut(): ActiveDirExists
    {
        $environment = $this->environment->reveal();
        $filesystem = $this->filesystem->reveal();
        $translatableFactory = self::createTranslatableFactory();

        return new ActiveDirExists($environment, $filesystem, $translatableFactory);
    }

    /**
     * @covers ::doAssertIsFulfilled
     * @covers ::getFulfilledStatusMessage
     */
    public function testFulfilled(): void
    {
        $this->filesystem
            ->fileExists(self::activeDirPath())
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE)
            ->willReturn(true);
        $this->filesystem
            ->isDir(self::activeDirPath())
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);

        $this->doTestFulfilled(self::FULFILLED_STATUS_MESSAGE);
    }

    /** @covers ::doAssertIsFulfilled */
    public function testDoesNotExist(): void
    {
        $message = 'The active directory does not exist.';
        $this->filesystem
            ->fileExists(self::activeDirPath())
            ->willReturn(false);

        $this->doTestUnfulfilled($message);
    }

    /** @covers ::doAssertIsFulfilled */
    public function testIsNotADirectory(): void
    {
        $message = 'The active directory is not actually a directory.';
        $this->filesystem
            ->isDir(self::activeDirPath())
            ->willReturn(false);

        $this->doTestUnfulfilled($message);
    }
}
