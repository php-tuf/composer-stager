<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree;

use PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\CommitterPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\CommonPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\NoUnsupportedLinksExistInterface;
use PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\StagingDirIsReadyInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\CodebaseContainsNoSymlinksInterface;

final class CommitterPreconditions extends AbstractPreconditionsTree implements CommitterPreconditionsInterface
{
    public function __construct(
        CodebaseContainsNoSymlinksInterface $codebaseContainsNoSymlinks,
        CommonPreconditionsInterface $commonPreconditions,
        NoUnsupportedLinksExistInterface $noUnsupportedLinksExist,
        StagingDirIsReadyInterface $stagingDirIsReady
    ) {
        /** @var array<\PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface> $children */
        $children = func_get_args();

        parent::__construct(...$children);
    }

    public function getName(): string
    {
        return 'Committer preconditions'; // @codeCoverageIgnore
    }

    public function getDescription(): string
    {
        return 'The preconditions for making staged changes live.'; // @codeCoverageIgnore
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The preconditions for making staged changes live are fulfilled.';
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        return 'The preconditions for making staged changes live are unfulfilled.';
    }
}
