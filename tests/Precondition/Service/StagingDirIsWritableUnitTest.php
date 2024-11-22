<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirIsWritable;
use PHPUnit\Framework\Attributes\CoversClass;
use Prophecy\Prophecy\ObjectProphecy;

#[CoversClass(StagingDirIsWritable::class)]
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
        $translatableFactory = self::createTranslatableFactory();

        return new StagingDirIsWritable($environment, $filesystem, $translatableFactory);
    }

    public function testFulfilled(): void
    {
        $this->filesystem
            ->isWritable(self::stagingDirPath())
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE)
            ->willReturn(true);

        $this->doTestFulfilled(self::FULFILLED_STATUS_MESSAGE);
    }

    public function testUnfulfilled(): void
    {
        $message = 'The staging directory is not writable.';
        $this->filesystem
            ->isWritable(self::stagingDirPath())
            ->willReturn(false);

        $this->doTestUnfulfilled($message);
    }
}
