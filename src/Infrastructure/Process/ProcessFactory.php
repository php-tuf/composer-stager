<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process;

use PhpTuf\ComposerStager\Exception\LogicException;
use Symfony\Component\Process\Process;

/**
 * @internal
 */
class ProcessFactory
{
    /**
     * @param string[] $array
     * @param mixed ...$args
     *
     * @return \Symfony\Component\Process\Process<\Generator>
     *
     * @throws \PhpTuf\ComposerStager\Exception\LogicException
     */
    public function create(array $array, ...$args): Process
    {
        try {
            return new Process($array, ...$args);
        } catch (\Symfony\Component\Process\Exception\LogicException $e) { // @codeCoverageIgnore
            throw new LogicException($e->getMessage(), $e->getCode(), $e); // @codeCoverageIgnore
        }
    }
}
