<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\Internal\Precondition\Service\NoAbsoluteSymlinksExist;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\NoAbsoluteSymlinksExist
 *
 * @covers ::exitEarly
 * @covers ::getFulfilledStatusMessage
 */
final class NoAbsoluteSymlinksExistUnitTest extends FileIteratingPreconditionUnitTestCase
{
    protected function createSut(): NoAbsoluteSymlinksExist
    {
        $environment = $this->environment->reveal();
        $fileFinder = $this->fileFinder->reveal();
        $filesystem = $this->filesystem->reveal();
        $pathFactory = $this->pathFactory->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new NoAbsoluteSymlinksExist($environment, $fileFinder, $filesystem, $pathFactory, $translatableFactory);
    }

    protected function fulfilledStatusMessage(): string
    {
        return 'There are no absolute links in the codebase.';
    }
}
