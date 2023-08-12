<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Service;

use PhpTuf\ComposerStager\API\Translation\Service\DomainOptionsInterface;
use PhpTuf\ComposerStager\Tests\TestUtils\Domain;

final class TestDomainOptions implements DomainOptionsInterface
{
    public function __construct(
        private readonly string $default = Domain::DEFAULT,
        private readonly string $exceptions = Domain::EXCEPTIONS,
    ) {
    }

    public function default(): string
    {
        return $this->default;
    }

    public function exceptions(): string
    {
        return $this->exceptions;
    }
}
