<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestDoubles\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\PreconditionInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;

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
        return TranslationTestHelper::createTranslatableMessage($this->name);
    }

    public function getDescription(): TranslatableInterface
    {
        return TranslationTestHelper::createTranslatableMessage($this->description);
    }

    public function getStatusMessage(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
        int $timeout = ProcessInterface::DEFAULT_TIMEOUT,
    ): TranslatableInterface {
        return TranslationTestHelper::createTranslatableMessage($this->statusMessage);
    }

    public function isFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
        int $timeout = ProcessInterface::DEFAULT_TIMEOUT,
    ): bool {
        return $this->isFulfilled;
    }

    public function assertIsFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
        int $timeout = ProcessInterface::DEFAULT_TIMEOUT,
    ): void {
        if ($this->isFulfilled) {
            return;
        }

        throw new PreconditionException($this, TranslationTestHelper::createTranslatableMessage());
    }

    public function getLeaves(): array
    {
        return [];
    }
}
