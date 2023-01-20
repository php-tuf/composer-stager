<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Precondition\NoHardLinksExistInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

final class NoHardLinksExist extends AbstractLinkIteratingPrecondition implements NoHardLinksExistInterface
{
    public function getName(): string
    {
        return 'No hard links exist'; // @codeCoverageIgnore
    }

    public function getDescription(): string
    {
        return 'The codebase cannot contain hard links.'; // @codeCoverageIgnore
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'There are no hard links in the codebase.';
    }

    protected function getDefaultUnfulfilledStatusMessage(): string
    {
        return 'The %s directory at "%s" contains hard links, which is not supported. The first one is "%s".';
    }

    protected function isSupportedLink(PathInterface $file, PathInterface $directory): bool
    {
        return !$this->filesystem->isHardLink($file);
    }
}
