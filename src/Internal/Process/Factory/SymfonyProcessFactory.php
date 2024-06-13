<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Process\Factory;

use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Internal\SymfonyProcess\Value\Exception\ExceptionInterface as SymfonyExceptionInterface;
use PhpTuf\ComposerStager\Internal\SymfonyProcess\Value\Process as SymfonyProcess;
use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableAwareTrait;

/**
 * @package Process
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class SymfonyProcessFactory implements SymfonyProcessFactoryInterface
{
    use TranslatableAwareTrait;

    public function __construct(TranslatableFactoryInterface $translatableFactory)
    {
        $this->setTranslatableFactory($translatableFactory);
    }

    public function create(array $command, ?PathInterface $cwd = null, array $env = []): SymfonyProcess
    {
        if ($cwd instanceof PathInterface) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $cwd = $cwd->absolute();
        }

        try {
            return new SymfonyProcess($command, $cwd, $env);
        } catch (SymfonyExceptionInterface $e) {
            throw new LogicException($this->t(
                'Failed to create process: %details',
                $this->p(['%details' => $e->getMessage()]),
                $this->d()->exceptions(),
            ), 0, $e);
        }
    }
}
