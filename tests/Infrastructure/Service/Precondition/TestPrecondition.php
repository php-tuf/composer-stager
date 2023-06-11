<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface;
use PhpTuf\ComposerStager\Domain\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathListInterface;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableMessage;

final class TestPrecondition implements PreconditionInterface
{
    public function __construct(
        private readonly string $name = 'Name',
        private readonly string $description = 'Description',
        private readonly string $statusMessage = 'Status message',
        private readonly bool $isFulfilled = true,
    ) {
    }

    public function getName(): TranslatableInterface
    {
        return new TestTranslatableMessage($this->name);
    }

    public function getDescription(): TranslatableInterface
    {
        return new TestTranslatableMessage($this->description);
    }

    public function getStatusMessage(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
    ): TranslatableInterface {
        return new TestTranslatableMessage($this->statusMessage);
    }

    public function isFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
    ): bool {
        return $this->isFulfilled;
    }

    public function assertIsFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
    ): void {
        if ($this->isFulfilled) {
            return;
        }

        throw new PreconditionException($this, new TestTranslatableMessage());
    }

    public function getLeaves(): array
    {
        return [];
    }
}
