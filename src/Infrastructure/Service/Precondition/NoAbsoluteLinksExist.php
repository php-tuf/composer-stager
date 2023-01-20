<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Precondition\NoAbsoluteLinksExistInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

final class NoAbsoluteLinksExist extends AbstractLinkIteratingPrecondition implements NoAbsoluteLinksExistInterface
{
    public function getName(): string
    {
        return 'No absolute links exist'; // @codeCoverageIgnore
    }

    public function getDescription(): string
    {
        return 'The codebase cannot contain absolute links.'; // @codeCoverageIgnore
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'There are no absolute links in the codebase.';
    }

    protected function getDefaultUnfulfilledStatusMessage(): string
    {
        return 'The %s directory at "%s" contains absolute links, which is not supported. The first one is "%s".';
    }

    protected function isSupportedLink(PathInterface $file, PathInterface $directory): bool
    {
        return !$file->isAbsolute();
    }
}
