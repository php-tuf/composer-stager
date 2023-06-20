<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\Internal\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirDoesNotExist;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Service\TestTranslator;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirDoesNotExist
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
final class StagingDirDoesNotExistUnitTest extends PreconditionTestCase
{
    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(FilesystemInterface::class);

        parent::setUp();
    }

    protected function createSut(): StagingDirDoesNotExist
    {
        $filesystem = $this->filesystem->reveal();
        $translatableFactory = new TestTranslatableFactory();
        $translator = new TestTranslator();

        return new StagingDirDoesNotExist($filesystem, $translatableFactory, $translator);
    }

    public function testFulfilled(): void
    {
        $this->filesystem
            ->exists($this->stagingDir)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE)
            ->willReturn(false);

        $this->doTestFulfilled('The staging directory does not already exist.');
    }

    public function testUnfulfilled(): void
    {
        $this->filesystem
            ->exists($this->stagingDir)
            ->willReturn(true);

        $this->doTestUnfulfilled('The staging directory already exists.');
    }
}
