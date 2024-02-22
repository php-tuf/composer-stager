<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestDoubles\Translation\Value;

use PhpTuf\ComposerStager\API\Translation\Service\TranslatorInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;
use PhpTuf\ComposerStager\Tests\TestDoubles\Translation\Service\TestDomainOptions;
use PhpTuf\ComposerStager\Tests\TestUtils\TranslationTestHelper;

final class TestTranslatableExceptionMessage extends TestTranslatableMessage
{
    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(
        private readonly string $message = '',
        private readonly ?TranslationParametersInterface $parameters = null,
        private readonly ?string $domain = TestDomainOptions::EXCEPTIONS,
    ) {
    }

    public function trans(?TranslatorInterface $translator = null, ?string $locale = null): string
    {
        $translator ??= TranslationTestHelper::createTranslator();
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
