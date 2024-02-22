<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestDoubles\Translation\Value;

use PhpTuf\ComposerStager\API\Translation\Service\TranslatorInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;
use PhpTuf\ComposerStager\Internal\Translation\Service\DomainOptions;
use PhpTuf\ComposerStager\Tests\TestUtils\TranslationTestHelper;

/** @phpcs:disable SlevomatCodingStandard.Classes.RequireAbstractOrFinal.ClassNeitherAbstractNorFinal */
class TestTranslatableMessage implements TranslatableInterface
{
    public function __construct(
        private readonly string $message = '',
        private readonly ?TranslationParametersInterface $parameters = null,
        private ?string $domain = null,
    ) {
        if ($domain !== null) {
            return;
        }

        $domainOptions = new DomainOptions();
        $this->domain = $domainOptions->exceptions();
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
