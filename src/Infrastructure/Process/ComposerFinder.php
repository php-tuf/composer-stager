<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process;

use PhpTuf\ComposerStager\Exception\FileNotFoundException;
use Symfony\Component\Process\ExecutableFinder;

/**
 * @internal
 */
class ComposerFinder
{
    /**
     * @var \PhpTuf\ComposerStager\Exception\FileNotFoundException|string
     */
    private $cache = '';

    /**
     * @var \Symfony\Component\Process\ExecutableFinder
     */
    private $executableFinder;

    public function __construct(ExecutableFinder $executableFinder)
    {
        $this->executableFinder = $executableFinder;
    }

    /**
     * @throws \PhpTuf\ComposerStager\Exception\FileNotFoundException
     */
    public function find(): string
    {
        // Throw cached exception.
        if ($this->cache instanceof FileNotFoundException) {
            throw $this->cache;
        }

        // Return cached path.
        if ($this->cache !== '') {
            return $this->cache;
        }

        // Look for Composer.
        $this->executableFinder->addSuffix('.phar');
        $path = $this->executableFinder->find('composer');

        // Cache and throw exception if not found.
        if (is_null($path)) {
            $this->cache = new FileNotFoundException(
                '',
                'The Composer executable cannot be found. Make sure it is installed and in the $PATH.'
            );
            throw $this->cache;
        }

        // Cache and return path if found.
        $this->cache = $path;
        return $path;
    }
}
