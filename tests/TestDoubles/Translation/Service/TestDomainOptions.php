<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestDoubles\Translation\Service;

use PhpTuf\ComposerStager\API\Translation\Service\DomainOptionsInterface;
use PhpTuf\ComposerStager\Tests\TestUtils\TranslationTestHelper;

final class TestDomainOptions implements DomainOptionsInterface
{
    public const EXCEPTIONS = TranslationTestHelper::DOMAIN_EXCEPTIONS;
    public const DEFAULT = TranslationTestHelper::DOMAIN_DEFAULT;

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
