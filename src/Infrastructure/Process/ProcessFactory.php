<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process;

use PhpTuf\ComposerStager\Exception\LogicException;
use Symfony\Component\Process\Exception\ExceptionInterface;
use Symfony\Component\Process\Process;

final class ProcessFactory implements ProcessFactoryInterface
{
    public function create(array $command): Process
    {
        try {
            return new Process($command);
        } catch (ExceptionInterface $e) { // @codeCoverageIgnore
            throw new LogicException($e->getMessage(), (int) $e->getCode(), $e); // @codeCoverageIgnore
        }
    }
}
