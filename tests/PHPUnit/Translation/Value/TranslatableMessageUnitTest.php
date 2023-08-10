<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Value;

use PhpTuf\ComposerStager\API\Translation\Service\TranslatorInterface;
use PhpTuf\ComposerStager\Internal\Translation\Service\DomainOptions;
use PhpTuf\ComposerStager\Internal\Translation\Service\LocaleOptions;
use PhpTuf\ComposerStager\Internal\Translation\Service\SymfonyTranslatorProxy;
use PhpTuf\ComposerStager\Internal\Translation\Service\Translator;
use PhpTuf\ComposerStager\Internal\Translation\Value\TranslatableMessage;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslationParameters;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Translation\Value\TranslatableMessage */
final class TranslatableMessageUnitTest extends TestCase
{
    private TranslatorInterface|ObjectProphecy $translator;

    protected function setUp(): void
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
        array $givenOptionalConstructorArguments,
        array $expectedTransArguments,
        array $locale,
        string $expectedTranslation,
    ): void {
        $expectedTransArguments = array_values($expectedTransArguments);
        $this->translator
            ->trans(Argument::cetera());
        $this->translator
            ->trans(...$expectedTransArguments)
            ->shouldBeCalledOnce()
            ->willReturn($expectedTranslation);
        $givenOptionalConstructorArguments = array_values($givenOptionalConstructorArguments);
        $sut = new TranslatableMessage($message, $this->translator->reveal(), ...$givenOptionalConstructorArguments);

        /** Call once with the spy translator to make sure it passes the correct arguments through. */
        $sut->trans(null, ...$locale);

        /** Call again with a real translator to assert on actual results. */
        $actualTranslation = $sut->trans(new Translator(
            new DomainOptions(),
            new LocaleOptions(),
            new SymfonyTranslatorProxy(),
        ));

        self::assertSame($expectedTranslation, $actualTranslation, 'Returned correct translation.');
        self::assertSame($expectedTranslation, (string) $sut, 'Returned correct typecast string value.');
    }

    public function providerBasicFunctionality(): array
    {
        return [
            'Minimum values' => [
                'message' => 'Minimum values',
                'givenOptionalConstructorArguments' => [],
                'expectedTransArguments' => [
                    'message' => 'Minimum values',
                    'parameters' => null,
                    'domain' => null,
                    'translator' => null,
                    'locale' => null,
                ],
                'locale' => [],
                'expectedTranslation' => 'Minimum values',
            ],
            'Nullable values' => [
                'message' => 'Nullable values',
                'givenOptionalConstructorArguments' => [
                    'parameters' => null,
                    'domain' => null,
                    'locale' => null,
                ],
                'expectedTransArguments' => [
                    'message' => 'Nullable values',
                    'parameters' => null,
                    'domain' => null,
                    'locale' => null,
                ],
                'locale' => [null],
                'expectedTranslation' => 'Nullable values',
            ],
            'Simple values' => [
                'message' => 'Simple values',
                'givenOptionalConstructorArguments' => [
                    'parameters' => new TestTranslationParameters(),
                    'domain' => 'a_domain',
                ],
                'expectedTransArguments' => [
                    'message' => 'Simple values',
                    'parameters' => new TestTranslationParameters(),
                    'domain' => 'a_domain',
                    'locale' => 'a_locale',
                ],
                'locale' => ['a_locale'],
                'expectedTranslation' => 'Simple values',
            ],
            'Simple substitution' => [
                'message' => 'A %mood string',
                'givenOptionalConstructorArguments' => [
                    'parameters' => new TestTranslationParameters(['%mood' => 'happy']),
                ],
                'expectedTransArguments' => [
                    'message' => 'A %mood string',
                    'parameters' => new TestTranslationParameters(['%mood' => 'happy']),
                    'domain' => null,
                    'locale' => null,
                ],
                'locale' => [],
                'expectedTranslation' => 'A happy string',
            ],
            'Multiple substitutions' => [
                'message' => 'A %mood %size string',
                'givenOptionalConstructorArguments' => [
                    'parameters' => new TestTranslationParameters([
                        '%mood' => 'happy',
                        '%size' => 'little',
                    ]),
                ],
                'expectedTransArguments' => [
                    'message' => 'A %mood %size string',
                    'parameters' => new TestTranslationParameters([
                        '%mood' => 'happy',
                        '%size' => 'little',
                    ]),
                    'domain' => null,
                    'locale' => null,
                ],
                'locale' => [],
                'expectedTranslation' => 'A happy little string',
            ],
        ];
    }

    /** @covers ::trans */
    public function testTransWithOptionalTranslatorArgument(): void
    {
        $constructorTranslator = $this->prophesize(TranslatorInterface::class);
        $constructorTranslator
            ->trans(Argument::cetera());
        $constructorTranslator
            ->trans(Argument::cetera())
            ->shouldNotBeCalled();
        $transTranslator = $this->prophesize(TranslatorInterface::class);
        $transTranslator
            ->trans(Argument::cetera());
        $transTranslator
            ->trans(Argument::cetera())
            ->shouldBeCalledOnce();
        $sut = new TranslatableMessage(__METHOD__, $constructorTranslator->reveal());

        $sut->trans($transTranslator->reveal());
    }
}
