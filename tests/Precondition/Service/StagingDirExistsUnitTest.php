<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\Infrastructure\Precondition\Service\StagingDirExists;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Service\TestTranslator;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\StagingDirExists
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\API\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\API\Translation\Factory\TranslatableAwareTrait
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\AbstractPrecondition
 *
 * @property \PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
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

    public function testUnfulfilled(): void
    {
        $this->filesystem
            ->exists($this->stagingDir)
            ->willReturn(false);

        $this->doTestUnfulfilled('The staging directory does not exist.');
    }
}
