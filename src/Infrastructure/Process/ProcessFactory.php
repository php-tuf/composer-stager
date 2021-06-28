<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process;

use PhpTuf\ComposerStager\Exception\LogicException;
use Symfony\Component\Process\Exception\ExceptionInterface;
use Symfony\Component\Process\Process;

/**
 * @internal
 */
final class ProcessFactory implements ProcessFactoryInterface
{
    public function create(array $command, ...$args): Process
    {
        try {
            return new Process($command, ...$args);
        } catch (ExceptionInterface $e) { // @codeCoverageIgnore
            throw new LogicException($e->getMessage(), $e->getCode(), $e); // @codeCoverageIgnore
        }
    }
}
