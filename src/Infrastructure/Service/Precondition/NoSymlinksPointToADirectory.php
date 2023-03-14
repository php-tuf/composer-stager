<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Precondition\NoSymlinksPointToADirectoryInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

final class NoSymlinksPointToADirectory extends AbstractFileIteratingPrecondition implements
    NoSymlinksPointToADirectoryInterface
{
    public function getName(): string
    {
        return 'No symlinks point to a directory'; // @codeCoverageIgnore
    }

    public function getDescription(): string
    {
        return 'The codebase cannot contain symlinks that point to a directory.'; // @codeCoverageIgnore
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'There are no symlinks that point to a directory.';
    }

    protected function getDefaultUnfulfilledStatusMessage(): string
    {
        return <<<'EOF'
The %s directory at "%s" contains symlinks that point to a directory, which is not supported. The first one is "%s".
EOF;
    }

    protected function isSupportedFile(PathInterface $file, PathInterface $codebaseRootDir): bool
    {
        if (!$this->filesystem->isSymlink($file)) {
            return true;
        }

        $target = $this->filesystem->readLink($file);

        return !$this->filesystem->isDir($target);
    }
}
