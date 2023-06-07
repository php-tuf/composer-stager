<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Translation;

use PhpTuf\ComposerStager\Domain\Service\Translation\TranslatorInterface;
use PhpTuf\ComposerStager\Domain\Value\Translation\TranslationParametersInterface;
use Symfony\Contracts\Translation\TranslatorTrait;

final class TestTranslator implements TranslatorInterface
{
    use TranslatorTrait {
        trans as symfonyTrans;
    }

    public function trans(
        string $id,
        ?TranslationParametersInterface $parameters = null,
        ?string $domain = null,
        ?string $locale = null,
    ): string {
        $parameters = $parameters instanceof TranslationParametersInterface
            ? $parameters->getAll()
            : [];

        return $this->symfonyTrans($id, $parameters, $domain, $locale);
    }

    public function getLocale(): string
    {
        return 'en_US';
    }
}
