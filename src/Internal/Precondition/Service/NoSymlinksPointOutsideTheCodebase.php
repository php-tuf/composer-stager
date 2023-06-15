<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\NoSymlinksPointOutsideTheCodebaseInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;

/**
 * @package Precondition
 *
 * @internal Don't depend on this class. It may be changed or removed at any time without notice.
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
                    'The %codebase_name directory at %codebase_root contains links that point outside the codebase, '
                    . 'which is not supported. The first one is %file.',
                    $this->p([
                        '%codebase_name' => $codebaseName,
                        '%codebase_root' => $codebaseRoot->resolved(),
                        '%file' => $file->resolved(),
                    ]),
                ),
            );
        }
    }

    /** @throws \PhpTuf\ComposerStager\API\Exception\IOException */
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
