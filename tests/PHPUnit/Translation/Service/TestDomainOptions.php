<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Service;

use PhpTuf\ComposerStager\API\Translation\Service\DomainOptionsInterface;
use PhpTuf\ComposerStager\Tests\TestCase;

final class TestDomainOptions implements DomainOptionsInterface
{
    public function __construct(
        private readonly string $default = TestCase::DOMAIN_DEFAULT,
        private readonly string $exceptions = TestCase::DOMAIN_EXCEPTIONS,
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
