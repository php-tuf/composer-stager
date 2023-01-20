<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree;

use PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\NoUnsupportedLinksExistInterface;

final class NoUnsupportedLinksExist extends AbstractPreconditionsTree implements NoUnsupportedLinksExistInterface
{
    public function __construct()
    {
        /** @var array<\PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface> $children */
        $children = func_get_args();

        parent::__construct(...$children);
    }

    public function getName(): string
    {
        return 'Unsupported links preconditions'; // @codeCoverageIgnore
    }

    public function getDescription(): string
    {
        return 'Preconditions concerning unsupported links.'; // @codeCoverageIgnore
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'There are no unsupported links in the codebase.';
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        // @todo Remove codeCoverageIgnore.
        return 'There are unsupported links in the codebase.'; // @codeCoverageIgnore
    }
}
