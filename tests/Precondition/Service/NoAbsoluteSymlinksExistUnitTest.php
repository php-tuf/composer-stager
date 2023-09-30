<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\Internal\Precondition\Service\NoAbsoluteSymlinksExist;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\NoAbsoluteSymlinksExist
 *
 * @covers ::assertIsSupportedFile
 * @covers ::exitEarly
 * @covers ::getFulfilledStatusMessage
 */
final class NoAbsoluteSymlinksExistUnitTest extends FileIteratingPreconditionUnitTestCase
{
    protected const NAME = 'No absolute links exist';
    protected const DESCRIPTION = 'The codebase cannot contain absolute links.';
    protected const FULFILLED_STATUS_MESSAGE = 'There are no absolute links in the codebase.';

    protected function createSut(): NoAbsoluteSymlinksExist
    {
        $environment = $this->environment->reveal();
        $fileFinder = $this->fileFinder->reveal();
        $filesystem = $this->filesystem->reveal();
        $pathFactory = $this->pathFactory->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new NoAbsoluteSymlinksExist($environment, $fileFinder, $filesystem, $pathFactory, $translatableFactory);
    }
}
