<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirExistsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirIsReadyInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirIsWritableInterface;

/** @internal Don't instantiate this class directly. Get it from the service container via its interface. */
final class ActiveDirIsReady extends AbstractPreconditionsTree implements ActiveDirIsReadyInterface
{
    public function __construct(
        ActiveDirExistsInterface $activeDirExists,
        ActiveDirIsWritableInterface $activeDirIsWritable,
    ) {
        parent::__construct(...func_get_args());
    }

    public function getName(): string
    {
        return 'Active directory is ready'; // @codeCoverageIgnore
    }

    public function getDescription(): string
    {
        return 'The preconditions for using the active directory.'; // @codeCoverageIgnore
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The active directory is ready to use.';
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        return 'The active directory is not ready to use.';
    }
}
