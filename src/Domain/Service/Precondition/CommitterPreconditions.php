<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\Precondition;

final class CommitterPreconditions extends AbstractPrecondition implements CommitterPreconditionsInterface
{
    public function __construct(CommonPreconditionsInterface $preconditions)
    {
        /** @var array<\PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface> $children */
        $children = func_get_args();

        parent::__construct(...$children);
    }

    public static function getName(): string
    {
        return 'Committer preconditions'; // @codeCoverageIgnore
    }

    public static function getDescription(): string
    {
        return 'The preconditions for making staged changes live.'; // @codeCoverageIgnore
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The preconditions for making staged changes live are fulfilled.'; // @codeCoverageIgnore
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        return 'The preconditions for making staged changes live are unfulfilled.'; // @codeCoverageIgnore
    }
}
