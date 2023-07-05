<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\PreconditionInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
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
