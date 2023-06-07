<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Value\Translation;

use Error;
use LogicException;
use PhpTuf\ComposerStager\Domain\Service\Translation\TranslatorInterface;
use PhpTuf\ComposerStager\Domain\Value\Translation\TranslationParametersInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Translation\SymfonyTranslatorProxy;
use PhpTuf\ComposerStager\Infrastructure\Service\Translation\Translator;
use PhpTuf\ComposerStager\Infrastructure\Value\Translation\TranslatableMessage;
use PhpTuf\ComposerStager\Tests\Infrastructure\Factory\Translation\TestTranslationParameters;
use PhpTuf\ComposerStager\Tests\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Value\Translation\TranslatableMessage
 *
 * @covers \PhpTuf\ComposerStager\Infrastructure\Service\Translation\Translator
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Translation\SymfonyTranslatorProxy
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Translation\Translator
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Translation\TranslatableMessage
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Translation\TranslationParameters
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
        $actualTranslation = $sut->trans(new Translator(new SymfonyTranslatorProxy()));

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

    public function providerTranslatorException(): array
    {
        return [
            [
                new Error('An Error'),
            ],
            [
                new LogicException('A LogicException'),
            ],
        ];
    }
}
