<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirExists;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirExists
 *
 * @covers ::__construct
 * @covers ::doAssertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 */
final class StagingDirExistsUnitTest extends PreconditionTestCase
{
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
        $translatableFactory = new TestTranslatableFactory();

        return new StagingDirExists($environment, $filesystem, $translatableFactory);
    }

    public function testFulfilled(): void
    {
        $this->filesystem
            ->exists(PathHelper::stagingDirPath())
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE)
            ->willReturn(true);

        $this->doTestFulfilled('The staging directory exists.');
    }

    /** @covers ::doAssertIsFulfilled */
    public function testUnfulfilled(): void
    {
        $message = 'The staging directory does not exist.';
        $this->filesystem
            ->exists(PathHelper::stagingDirPath())
            ->willReturn(false);

        $this->doTestUnfulfilled($message);
    }
}
