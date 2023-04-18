<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Precondition\NoAbsoluteSymlinksExistInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoHardLinksExistInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoLinksExistOnWindowsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoSymlinksPointOutsideTheCodebaseInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoSymlinksPointToADirectoryInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoUnsupportedLinksExistInterface;

/**
 * @package Precondition
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class NoUnsupportedLinksExist extends AbstractPreconditionsTree implements NoUnsupportedLinksExistInterface
{
    public function __construct(
        NoAbsoluteSymlinksExistInterface $noAbsoluteSymlinksExist,
        NoHardLinksExistInterface $noHardLinksExist,
        NoLinksExistOnWindowsInterface $noLinksExistOnWindows,
        NoSymlinksPointOutsideTheCodebaseInterface $noSymlinksPointOutsideTheCodebase,
        NoSymlinksPointToADirectoryInterface $noSymlinksPointToADirectory,
    ) {
        parent::__construct(...func_get_args());
    }

    public function getName(): string
    {
        return 'Unsupported links preconditions';
    }

    public function getDescription(): string
    {
        return 'Preconditions concerning unsupported links.';
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
