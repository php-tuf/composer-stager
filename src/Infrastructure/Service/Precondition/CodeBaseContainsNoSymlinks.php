<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Precondition\CodebaseContainsNoSymlinksInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
final class CodeBaseContainsNoSymlinks extends AbstractLinkIteratingPrecondition implements CodebaseContainsNoSymlinksInterface
{
    public function getName(): string
    {
        return 'Codebase contains no symlinks'; // @codeCoverageIgnore
    }

    public function getDescription(): string
    {
        return 'The codebase cannot contain symlinks.'; // @codeCoverageIgnore
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The codebase contains no symlinks.';
    }

    protected function getDefaultUnfulfilledStatusMessage(): string
    {
        return 'The %s directory at "%s" contains symlinks, which is not supported. The first one is "%s".';
    }

    protected function isSupportedLink(PathInterface $file, PathInterface $directory): bool
    {
        return !$this->filesystem->isLink($file);
    }
}
