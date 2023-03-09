<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Precondition\NoSymlinksPointOutsideTheCodebaseInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

final class NoSymlinksPointOutsideTheCodebase extends AbstractFileIteratingPrecondition implements
    NoSymlinksPointOutsideTheCodebaseInterface
{
    public function getName(): string
    {
        return 'No links point outside the codebase'; // @codeCoverageIgnore
    }

    public function getDescription(): string
    {
        return 'The codebase cannot contain links that point outside the codebase.'; // @codeCoverageIgnore
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'There are no links that point outside the codebase.';
    }

    protected function getDefaultUnfulfilledStatusMessage(): string
    {
        return <<<'EOF'
The %s directory at "%s" contains links that point outside the codebase, which is not supported. The first one is "%s".
EOF;
    }

    protected function isSupportedFile(PathInterface $file, PathInterface $codebaseRootDir): bool
    {
        if (!$this->filesystem->isSymlink($file)) {
            return true;
        }

        return !$this->linkPointsOutsidePath($file, $codebaseRootDir);
    }

    /** @throws \PhpTuf\ComposerStager\Domain\Exception\IOException */
    private function linkPointsOutsidePath(PathInterface $link, PathInterface $path): bool
    {
        $target = $this->filesystem->readLink($link);

        return !$this->isDescendant($target->resolve(), $path->resolve());
    }

    private function isDescendant(string $descendant, string $ancestor): bool
    {
        $ancestor .= DIRECTORY_SEPARATOR;

        return str_starts_with($descendant, $ancestor);
    }
}
