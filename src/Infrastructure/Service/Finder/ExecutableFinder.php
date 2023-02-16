<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Finder;

use PhpTuf\ComposerStager\Domain\Exception\LogicException;
use Symfony\Component\Process\ExecutableFinder as SymfonyExecutableFinder;

final class ExecutableFinder implements ExecutableFinderInterface
{
    private SymfonyExecutableFinder $symfonyExecutableFinder;

    public function __construct(SymfonyExecutableFinder $symfonyExecutableFinder)
    {
        $this->symfonyExecutableFinder = $symfonyExecutableFinder;
    }

    public function find(string $name): string
    {
        // Look for executable.
        $this->symfonyExecutableFinder->addSuffix('.phar');
        $path = $this->symfonyExecutableFinder->find($name);

        // Cache and throw exception if not found.
        if ($path === null) {
            throw new LogicException(
                sprintf('The "%s" executable cannot be found. Make sure it\'s installed and in the $PATH.', $name),
            );
        }

        return $path;
    }
}
