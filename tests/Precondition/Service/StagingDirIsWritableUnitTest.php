<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirIsWritable;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirIsWritable
 *
 * @covers ::__construct
 * @covers ::doAssertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 */
final class StagingDirIsWritableUnitTest extends PreconditionTestCase
{
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

    public function testFulfilled(): void
    {
        $this->filesystem
            ->isWritable(PathHelper::stagingDirPath())
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE)
            ->willReturn(true);

        $this->doTestFulfilled('The staging directory is writable.');
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
