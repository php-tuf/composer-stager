<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Precondition\NoHardLinksExistInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

/**
 * @package Precondition
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class NoHardLinksExist extends AbstractFileIteratingPrecondition implements NoHardLinksExistInterface
{
    public function getName(): string
    {
        return 'No hard links exist';
    }

    public function getDescription(): string
    {
        return 'The codebase cannot contain hard links.';
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'There are no hard links in the codebase.';
    }

    protected function getDefaultUnfulfilledStatusMessage(): string
    {
        return 'The %s directory at "%s" contains hard links, which is not supported. The first one is "%s".';
    }

    protected function isSupportedFile(PathInterface $file, PathInterface $codebaseRootDir): bool
    {
        return !$this->filesystem->isHardLink($file);
    }
}
