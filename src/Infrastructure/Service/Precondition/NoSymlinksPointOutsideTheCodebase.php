<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoSymlinksPointOutsideTheCodebaseInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\Translation\TranslatableInterface;

/**
 * @package Precondition
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class NoSymlinksPointOutsideTheCodebase extends AbstractFileIteratingPrecondition implements
    NoSymlinksPointOutsideTheCodebaseInterface
{
    public function getName(): TranslatableInterface
    {
        return $this->t('No symlinks point outside the codebase');
    }

    public function getDescription(): TranslatableInterface
    {
        return $this->t('The codebase cannot contain symlinks that point outside the codebase.');
    }

    protected function getFulfilledStatusMessage(): TranslatableInterface
    {
        return $this->t('There are no symlinks that point outside the codebase.');
    }

    protected function assertIsSupportedFile(
        string $codebaseName,
        PathInterface $codebaseRoot,
        PathInterface $file,
    ): void {
        if (!$this->filesystem->isSymlink($file)) {
            return;
        }

        if ($this->linkPointsOutsidePath($file, $codebaseRoot)) {
            throw new PreconditionException(
                $this,
                $this->t(
                    'The %codebase_name directory at "%codebase_root" contains links that point outside the codebase, '
                    . 'which is not supported. The first one is "%file".',
                    $this->p([
                        '%codebase_name' => $codebaseName,
                        '%codebase_root' => $codebaseRoot->resolved(),
                        '%file' => $file->resolved(),
                    ]),
                ),
            );
        }
    }

    /** @throws \PhpTuf\ComposerStager\Domain\Exception\IOException */
    private function linkPointsOutsidePath(PathInterface $link, PathInterface $path): bool
    {
        $target = $this->filesystem->readLink($link);

        return !$this->isDescendant($target->resolved(), $path->resolved());
    }

    private function isDescendant(string $descendant, string $ancestor): bool
    {
        $ancestor .= DIRECTORY_SEPARATOR;

        return str_starts_with($descendant, $ancestor);
    }
}
