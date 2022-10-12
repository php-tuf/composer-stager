<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition;

final class TestPrecondition extends AbstractPrecondition
{
    public string $description = 'Description';
    public string $fulfilledStatusMessage = 'Fulfilled';
    public bool $isFulfilled = true;
    public string $name = 'Name';
    public string $unfulfilledStatusMessage = 'Unfulfilled';

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    protected function getFulfilledStatusMessage(): string
    {
        return $this->fulfilledStatusMessage;
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        return $this->unfulfilledStatusMessage;
    }

    public function isFulfilled(PathInterface $activeDir, PathInterface $stagingDir): bool
    {
        return $this->isFulfilled;
    }
}
