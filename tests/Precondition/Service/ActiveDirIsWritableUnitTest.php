<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveDirIsWritable;
use PHPUnit\Framework\Attributes\CoversClass;
use Prophecy\Prophecy\ObjectProphecy;

#[CoversClass(ActiveDirIsWritable::class)]
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
        $translatableFactory = self::createTranslatableFactory();

        return new ActiveDirIsWritable($environment, $filesystem, $translatableFactory);
    }

    public function testFulfilled(): void
    {
        $this->filesystem
            ->isWritable(self::activeDirPath())
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE)
            ->willReturn(true);

        $this->doTestFulfilled(self::FULFILLED_STATUS_MESSAGE);
    }

    public function testUnfulfilled(): void
    {
        $message = 'The active directory is not writable.';
        $this->filesystem
            ->isWritable(self::activeDirPath())
            ->willReturn(false);

        $this->doTestUnfulfilled($message);
    }
}
