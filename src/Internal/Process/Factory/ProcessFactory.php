<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Process\Factory;

use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableAwareTrait;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\API\Translation\Value\DomainInterface;
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

    /**
     * @codeCoverageIgnore It's impractical to test a failure creating a Symfony
     *   process since it depends on a host configuration. It should be possible
     *   to overcome this limitation through the introduction of a Symfony Process
     *   proxy in the future.
     */
    public function create(array $command): Process
    {
        try {
            return new Process($command);
        } catch (SymfonyExceptionInterface $e) {
            throw new LogicException($this->t(
                'Failed to create process: %details',
                $this->p(['%details' => $e->getMessage()]),
                DomainInterface::EXCEPTIONS,
            ), 0, $e);
        }
    }
}
