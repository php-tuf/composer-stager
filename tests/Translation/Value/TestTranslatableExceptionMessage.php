<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Value;

use PhpTuf\ComposerStager\API\Translation\Service\TranslatorInterface;
use PhpTuf\ComposerStager\API\Translation\Value\Domain;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;

final class TestTranslatableExceptionMessage extends TestTranslatableMessage
{
    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(
        private readonly string $message = '',
        private readonly ?TranslationParametersInterface $parameters = null,
        private readonly ?string $domain = Domain::EXCEPTIONS,
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
