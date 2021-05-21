<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process;

use PhpTuf\ComposerStager\Exception\IOException;
use Symfony\Component\Process\ExecutableFinder as SymfonyExecutableFinder;

/**
 * @internal
 */
class ExecutableFinder
{
    /**
     * @var \PhpTuf\ComposerStager\Exception\IOException[]|string[]
     */
    private $cache = [];

    /**
     * @var \Symfony\Component\Process\ExecutableFinder
     */
    private $symfonyExecutableFinder;

    public function __construct(SymfonyExecutableFinder $symfonyExecutableFinder)
    {
        $this->symfonyExecutableFinder = $symfonyExecutableFinder;
    }

    /**
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     */
    public function find(string $name): string
    {
        $cache = $this->getCache($name);

        // Throw cached exception.
        if ($cache instanceof IOException) {
            throw $cache;
        }

        // Return cached path.
        if ($cache !== '') {
            return $cache;
        }

        // Look for executable.
        $this->symfonyExecutableFinder->addSuffix('.phar');
        $path = $this->symfonyExecutableFinder->find($name);

        // Cache and throw exception if not found.
        if (is_null($path)) {
            $cache = new IOException(
                sprintf('The "%s" executable cannot be found. Make sure it is installed and in the $PATH.', $name)
            );
            $this->setCache($name, $cache);
            throw $cache;
        }

        // Cache and return path if found.
        $cache = $path;
        $this->setCache($name, $cache);
        return $path;
    }

    /**
     * @param string $commandName
     *
     * @return mixed
     */
    private function getCache(string $commandName)
    {
        if (!array_key_exists($commandName, $this->cache)) {
            return '';
        }
        return $this->cache[$commandName];
    }

    /**
     * @param string $commandName
     * @param string|\PhpTuf\ComposerStager\Exception\IOException $value
     */
    private function setCache(string $commandName, $value): void
    {
        $this->cache[$commandName] = $value;
    }
}
