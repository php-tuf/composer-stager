<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

final class StagerPreconditions extends AbstractPrecondition implements StagerPreconditionsInterface
{
    public static function getName(): string
    {
        return 'Stager preconditions'; // @codeCoverageIgnore
    }

    public static function getDescription(): string
    {
        return 'The preconditions for staging Composer commands.'; // @codeCoverageIgnore
    }

    public function isFulfilled(PathInterface $activeDir, PathInterface $stagingDir): bool
    {
        return true; // @codeCoverageIgnore
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The preconditions for staging Composer commands are fulfilled.'; // @codeCoverageIgnore
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        return 'The preconditions for staging Composer commands are unfulfilled.'; // @codeCoverageIgnore
    }
}
