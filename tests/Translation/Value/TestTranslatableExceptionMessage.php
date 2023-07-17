<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Value;

use PhpTuf\ComposerStager\API\Translation\Service\TranslatorInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\Translation\Service\TestTranslator;

final class TestTranslatableExceptionMessage extends TestTranslatableMessage
{
    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(
        private readonly string $message = '',
        private readonly ?TranslationParametersInterface $parameters = null,
        private readonly ?string $domain = TestCase::DOMAIN_EXCEPTIONS,
    ) {
    }

    public function trans(?TranslatorInterface $translator = null, ?string $locale = null): string
    {
        $translator ??= new TestTranslator();
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
