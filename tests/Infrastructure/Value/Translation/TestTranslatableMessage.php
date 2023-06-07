<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Value\Translation;

use PhpTuf\ComposerStager\Domain\Service\Translation\TranslatorInterface;
use PhpTuf\ComposerStager\Domain\Value\Translation\TranslatableInterface;
use PhpTuf\ComposerStager\Domain\Value\Translation\TranslationParametersInterface;

final class TestTranslatableMessage implements TranslatableInterface
{
    public function __construct(
        private readonly string $message = '',
        private readonly ?TranslationParametersInterface $parameters = null,
        private readonly ?string $domain = null,
    ) {
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        $parameters = $this->parameters instanceof TranslationParametersInterface
            ? $this->parameters
            : new TestTranslationParameters();

        return $translator->trans($this->message, $parameters, $this->domain, $locale);
    }

    public function __toString(): string
    {
        return $this->message;
    }
}
