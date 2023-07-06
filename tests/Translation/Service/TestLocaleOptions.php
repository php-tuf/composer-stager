<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Service;

use PhpTuf\ComposerStager\API\Translation\Service\LocaleOptionsInterface;

final class TestLocaleOptions implements LocaleOptionsInterface
{
    public const DEFAULT = 'en_US';

    public function __construct(private readonly string $default = self::DEFAULT)
    {
    }

    public function default(): string
    {
        return $this->default;
    }
}
