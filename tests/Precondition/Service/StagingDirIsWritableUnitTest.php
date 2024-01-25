<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirIsWritable;
use PhpTuf\ComposerStager\Tests\TestDoubles\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirIsWritable
 *
 * @covers ::__construct
 * @covers ::doAssertIsFulfilled
 * @covers \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition::getStatusMessage
 */
final class StagingDirIsWritableUnitTest extends PreconditionUnitTestCase
{
    protected const NAME = 'Staging directory is writable';
    protected const DESCRIPTION = 'The staging directory must be writable before any operations can be performed.';
    protected const FULFILLED_STATUS_MESSAGE = 'The staging directory is writable.';

    private FilesystemInterface|ObjectProphecy $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(FilesystemInterface::class);

        parent::setUp();
    }

    protected function createSut(): StagingDirIsWritable
    {
        $environment = $this->environment->reveal();
        $filesystem = $this->filesystem->reveal();
        assert($filesystem instanceof FilesystemInterface);
        $translatableFactory = new TestTranslatableFactory();

        return new StagingDirIsWritable($environment, $filesystem, $translatableFactory);
    }

    /**
     * @covers ::doAssertIsFulfilled
     * @covers ::getFulfilledStatusMessage
     */
    public function testFulfilled(): void
    {
        $this->filesystem
            ->isWritable(PathHelper::stagingDirPath())
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE)
            ->willReturn(true);

        $this->doTestFulfilled(self::FULFILLED_STATUS_MESSAGE);
    }

    /** @covers ::doAssertIsFulfilled */
    public function testUnfulfilled(): void
    {
        $message = 'The staging directory is not writable.';
        $this->filesystem
            ->isWritable(PathHelper::stagingDirPath())
            ->willReturn(false);

        $this->doTestUnfulfilled($message);
    }
}
