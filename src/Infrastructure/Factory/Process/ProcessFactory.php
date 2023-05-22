<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Factory\Process;

use PhpTuf\ComposerStager\Domain\Exception\LogicException;
use Symfony\Component\Process\Exception\ExceptionInterface as SymfonyExceptionInterface;
use Symfony\Component\Process\Process;

/**
 * @package Process
 *
 * @api
 */
final class ProcessFactory implements ProcessFactoryInterface
{
    public function create(array $command): Process
    {
        try {
            return new Process($command);
        } catch (SymfonyExceptionInterface $e) { // @codeCoverageIgnore
            throw new LogicException($e->getMessage(), 0, $e); // @codeCoverageIgnore
        }
    }
}
