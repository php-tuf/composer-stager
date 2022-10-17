<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\CodebaseContainsNoSymlinksInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinderInterface;
use PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList;

final class CodeBaseContainsNoSymlinks extends AbstractPrecondition implements CodebaseContainsNoSymlinksInterface
{
    private RecursiveFileFinderInterface $fileFinder;

    private FilesystemInterface $filesystem;

    private string $unfulfilledStatusMessage = <<<'EOF'
The %s directory at "%s" contains symlinks, which is not supported. The first one is "%s".
EOF;

    public function __construct(RecursiveFileFinderInterface $fileFinder, FilesystemInterface $filesystem)
    {
        $this->fileFinder = $fileFinder;
        $this->filesystem = $filesystem;
    }

    public function getName(): string
    {
        return 'Codebase contains no symlinks'; // @codeCoverageIgnore
    }

    public function getDescription(): string
    {
        return 'The codebase cannot contain symlinks.'; // @codeCoverageIgnore
    }

    public function isFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null
    ): bool {
        $directories = [
            'active' => $activeDir,
            'staging' => $stagingDir,
        ];

        foreach ($directories as $name => $path) {
            try {
                $exclusions ??= new PathList([]);
                $exclusions->add([$stagingDir->resolve()]);
                $files = $this->findFiles($path, $exclusions);
            } catch (InvalidArgumentException|IOException $e) {
                // If something goes wrong searching for symlinks, don't throw an
                // exception--just consider the precondition unfulfilled and pass
                // details along to the user via the status message.
                $this->unfulfilledStatusMessage = $e->getMessage();

                return false;
            }

            foreach ($files as $file) {
                if (is_link($file)) {
                    $this->unfulfilledStatusMessage = sprintf(
                        $this->unfulfilledStatusMessage,
                        $name,
                        $path->resolve(),
                        $file,
                    );

                    return false;
                }
            }
        }

        return true;
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The codebase contains no symlinks.'; // @codeCoverageIgnore
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        // This message is defined dynamically so it can be overridden to pass
        // along an exception message from a dependency. See ::isFulfilled.
        return $this->unfulfilledStatusMessage;
    }

    /**
     * @return array<string>
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException
     * @throws \PhpTuf\ComposerStager\Domain\Exception\IOException
     */
    private function findFiles(PathInterface $path, ?PathListInterface $exclusions): array
    {
        // Ignore non-existent directories.
        if (!$this->filesystem->exists($path)) {
            return [];
        }

        return $this->fileFinder->find($path, $exclusions);
    }
}
