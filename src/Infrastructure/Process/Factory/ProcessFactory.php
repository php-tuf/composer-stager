<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Process\Factory;

use PhpTuf\ComposerStager\Domain\Exception\LogicException;
use PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableAwareTrait;
use PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableFactoryInterface;
use Symfony\Component\Process\Exception\ExceptionInterface as SymfonyExceptionInterface;
use Symfony\Component\Process\Process;

/**
 * @package Process
 *
 * @api
 */
final class ProcessFactory implements ProcessFactoryInterface
{
    use TranslatableAwareTrait;

    public function __construct(TranslatableFactoryInterface $translatableFactory)
    {
        $this->setTranslatableFactory($translatableFactory);
    }

    public function create(array $command): Process
    {
        try {
            return new Process($command);
        } catch (SymfonyExceptionInterface $e) { // @codeCoverageIgnore
            throw new LogicException($this->t($e->getMessage()), 0, $e); // @codeCoverageIgnore
        }
    }
}
