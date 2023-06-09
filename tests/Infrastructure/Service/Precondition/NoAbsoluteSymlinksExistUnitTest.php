<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoAbsoluteSymlinksExist;
use PhpTuf\ComposerStager\Tests\Infrastructure\Factory\Translation\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Infrastructure\Service\Translation\TestTranslator;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoAbsoluteSymlinksExist
 *
 * @covers ::assertIsFulfilled
 * @covers ::exitEarly
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Domain\Factory\Translation\TranslatableAwareTrait
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractFileIteratingPrecondition
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\PathList
 *
 * @property \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 */
final class NoAbsoluteSymlinksExistUnitTest extends FileIteratingPreconditionUnitTestCase
{
    protected function createSut(): NoAbsoluteSymlinksExist
    {
        $fileFinder = $this->fileFinder->reveal();
        $filesystem = $this->filesystem->reveal();
        $pathFactory = $this->pathFactory->reveal();
        $translatableFactory = new TestTranslatableFactory();
        $translator = new TestTranslator();

        return new NoAbsoluteSymlinksExist($fileFinder, $filesystem, $pathFactory, $translatableFactory, $translator);
    }

    protected function fulfilledStatusMessage(): string
    {
        return 'There are no absolute links in the codebase.';
    }
}
