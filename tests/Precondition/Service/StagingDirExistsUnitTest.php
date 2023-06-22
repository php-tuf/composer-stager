<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\Internal\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirExists;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Service\TestTranslator;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableExceptionMessage;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirExists
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\API\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\API\Translation\Factory\TranslatableAwareTrait
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition
 *
 * @property \PhpTuf\ComposerStager\Internal\Filesystem\Service\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 */
final class StagingDirExistsUnitTest extends PreconditionTestCase
{
    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(FilesystemInterface::class);

        parent::setUp();
    }

    protected function createSut(): StagingDirExists
    {
        $filesystem = $this->filesystem->reveal();
        $translatableFactory = new TestTranslatableFactory();
        $translator = new TestTranslator();

        return new StagingDirExists($filesystem, $translatableFactory, $translator);
    }

    public function testFulfilled(): void
    {
        $this->filesystem
            ->exists($this->stagingDir)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE)
            ->willReturn(true);

        $this->doTestFulfilled('The staging directory exists.');
    }

    /** @covers ::assertIsFulfilled */
    public function testUnfulfilled(): void
    {
        $message = new TestTranslatableExceptionMessage('The staging directory does not exist.');
        $this->filesystem
            ->exists($this->stagingDir)
            ->willReturn(false);

        $this->doTestUnfulfilled($message);
    }
}
