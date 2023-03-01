<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree;

use PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\NoUnsupportedLinksExistInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoAbsoluteLinksExistInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoHardLinksExistInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoLinksExistOnWindowsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoLinksPointOutsideTheCodebaseInterface;

final class NoUnsupportedLinksExist extends AbstractPreconditionsTree implements NoUnsupportedLinksExistInterface
{
    public function __construct(
        NoAbsoluteLinksExistInterface $noAbsoluteLinksExist,
        NoHardLinksExistInterface $noHardLinksExist,
        NoLinksExistOnWindowsInterface $noLinksExistOnWindows,
        NoLinksPointOutsideTheCodebaseInterface $noLinksPointOutsideTheCodebase
    ) {
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
        return 'There are unsupported links in the codebase.';
    }
}
