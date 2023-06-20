<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Process\Factory;

use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableAwareTrait;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use Symfony\Component\Process\Exception\ExceptionInterface as SymfonyExceptionInterface;
use Symfony\Component\Process\Process;

/**
 * @package Process
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
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
