<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Value;

use PhpTuf\ComposerStager\API\Translation\Service\TranslatorInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;
use PhpTuf\ComposerStager\Internal\Translation\Service\DomainOptions;
use PhpTuf\ComposerStager\Internal\Translation\Service\SymfonyTranslatorProxy;
use PhpTuf\ComposerStager\Internal\Translation\Service\Translator;
use PhpTuf\ComposerStager\Internal\Translation\Value\TranslatableMessage;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslationParameters;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Translation\Value\TranslatableMessage
 *
 * @covers \PhpTuf\ComposerStager\Internal\Translation\Service\Translator
 *
 * @uses \PhpTuf\ComposerStager\API\Exception\TranslatableExceptionTrait
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Service\DomainOptions
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Service\SymfonyTranslatorProxy
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Service\Translator
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Value\TranslatableMessage
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Value\TranslationParameters
 */
final class TranslatableMessageUnitTest extends TestCase
{
    private TranslatorInterface|ObjectProphecy $translator;

    public function setUp(): void
    {
        $this->translator = $this->prophesize(TranslatorInterface::class);
    }

    /**
     * @covers ::__construct
     * @covers ::__toString
     * @covers ::trans
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality(
        string $message,
        ?TranslationParametersInterface $parameters,
        ?string $domain,
        ?string $locale,
        string $expectedTranslation,
    ): void {
        $this->translator
            ->trans(Argument::cetera());
        $this->translator
            ->trans($message, $parameters, $domain, $locale)
            ->shouldBeCalledOnce()
            ->willReturn($expectedTranslation);
        $sut = new TranslatableMessage($message, $parameters, $domain);

        /** Call once with the spy translator to make sure it passes the correct arguments through. */
        $sut->trans($this->translator->reveal(), $locale);

        /** Call again with a real translator to assert on actual results. */
        $actualTranslation = $sut->trans(new Translator(new DomainOptions(), new SymfonyTranslatorProxy()));

        self::assertSame($expectedTranslation, $actualTranslation, 'Returned correct translation.');
        self::assertSame($message, (string) $sut, 'Returned correct typecast string value.');
    }

    public function providerBasicFunctionality(): array
    {
        return [
            'Empty values' => [
                'message' => '',
                'parameters' => new TestTranslationParameters(),
                'domain' => null,
                'locale' => null,
                'expectedTranslation' => '',
            ],
            'Simple values' => [
                'message' => 'A string',
                'parameters' => new TestTranslationParameters(),
                'domain' => 'a_domain',
                'locale' => 'a_locale',
                'expectedTranslation' => 'A string',
            ],
            'Simple substitution' => [
                'message' => 'A %mood string',
                'parameters' => new TestTranslationParameters(['%mood' => 'happy']),
                'domain' => null,
                'locale' => null,
                'expectedTranslation' => 'A happy string',
            ],
            'Multiple substitutions' => [
                'message' => 'A %mood %size string',
                'parameters' => new TestTranslationParameters([
                    '%mood' => 'happy',
                    '%size' => 'little',
                ]),
                'domain' => null,
                'locale' => null,
                'expectedTranslation' => 'A happy little string',
            ],
        ];
    }
}
