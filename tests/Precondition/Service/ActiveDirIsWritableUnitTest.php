<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveDirIsWritable;
use PhpTuf\ComposerStager\Tests\TestDoubles\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveDirIsWritable
 *
 * @covers ::__construct
 */
final class ActiveDirIsWritableUnitTest extends PreconditionUnitTestCase
{
    protected const NAME = 'Active directory is writable';
    protected const DESCRIPTION = 'The active directory must be writable before any operations can be performed.';
    protected const FULFILLED_STATUS_MESSAGE = 'The active directory is writable.';

    private FilesystemInterface|ObjectProphecy $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(FilesystemInterface::class);

        parent::setUp();
    }

    protected function createSut(): ActiveDirIsWritable
    {
        $environment = $this->environment->reveal();
        $filesystem = $this->filesystem->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new ActiveDirIsWritable($environment, $filesystem, $translatableFactory);
    }

    /**
     * @covers ::doAssertIsFulfilled
     * @covers ::getFulfilledStatusMessage
     */
    public function testFulfilled(): void
    {
        $this->filesystem
            ->isWritable(PathTestHelper::activeDirPath())
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE)
            ->willReturn(true);

        $this->doTestFulfilled(self::FULFILLED_STATUS_MESSAGE);
    }

    /** @covers ::doAssertIsFulfilled */
    public function testUnfulfilled(): void
    {
        $message = 'The active directory is not writable.';
        $this->filesystem
            ->isWritable(PathTestHelper::activeDirPath())
            ->willReturn(false);

        $this->doTestUnfulfilled($message);
    }
}
