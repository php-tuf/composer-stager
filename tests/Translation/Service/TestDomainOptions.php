<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Service;

use PhpTuf\ComposerStager\API\Translation\Service\DomainOptionsInterface;

final class TestDomainOptions implements DomainOptionsInterface
{
    public const EXCEPTIONS = 'exceptions';
    public const DEFAULT = 'messages';

    public function __construct(
        private readonly string $default = self::DEFAULT,
        private readonly string $exceptions = self::EXCEPTIONS,
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
