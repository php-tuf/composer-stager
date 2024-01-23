<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirDoesNotExist;
use PhpTuf\ComposerStager\Tests\Doubles\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirDoesNotExist
 *
 * @covers ::__construct
 * @covers ::doAssertIsFulfilled
 * @covers \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition::getStatusMessage
 */
final class StagingDirDoesNotExistUnitTest extends PreconditionUnitTestCase
{
    protected const NAME = 'Staging directory does not exist';
    protected const DESCRIPTION = 'The staging directory must not already exist before beginning the staging process.';
    protected const FULFILLED_STATUS_MESSAGE = 'The staging directory does not already exist.';

    private FilesystemInterface|ObjectProphecy $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(FilesystemInterface::class);

        parent::setUp();
    }

    protected function createSut(): StagingDirDoesNotExist
    {
        $environment = $this->environment->reveal();
        $filesystem = $this->filesystem->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new StagingDirDoesNotExist($environment, $filesystem, $translatableFactory);
    }

    /**
     * @covers ::doAssertIsFulfilled
     * @covers ::getFulfilledStatusMessage
     */
    public function testFulfilled(): void
    {
        $this->filesystem
            ->fileExists(PathHelper::stagingDirPath())
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE)
            ->willReturn(false);

        $this->doTestFulfilled(self::FULFILLED_STATUS_MESSAGE);
    }

    /** @covers ::doAssertIsFulfilled */
    public function testUnfulfilled(): void
    {
        $message = 'The staging directory already exists.';
        $this->filesystem
            ->fileExists(PathHelper::stagingDirPath())
            ->willReturn(true);

        $this->doTestUnfulfilled($message);
    }
}
