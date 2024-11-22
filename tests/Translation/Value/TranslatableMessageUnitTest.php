<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Value;

use PhpTuf\ComposerStager\API\Translation\Service\TranslatorInterface;
use PhpTuf\ComposerStager\Internal\Translation\Value\TranslatableMessage;
use PhpTuf\ComposerStager\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

#[CoversClass(TranslatableMessage::class)]
final class TranslatableMessageUnitTest extends TestCase
{
    private TranslatorInterface|ObjectProphecy $translator;

    protected function setUp(): void
    {
        $this->translator = $this->prophesize(TranslatorInterface::class);
    }

    #[DataProvider('providerBasicFunctionality')]
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
        $actualTranslation = $sut->trans(self::createTranslator());

        self::assertSame($expectedTranslation, $actualTranslation, 'Returned correct translation.');
        self::assertSame($expectedTranslation, (string) $sut, 'Returned correct typecast string value.');
    }

    public static function providerBasicFunctionality(): array
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
                    'parameters' => self::createTranslationParameters(),
                    'domain' => 'a_domain',
                ],
                'expectedTransArguments' => [
                    'message' => 'Simple values',
                    'parameters' => self::createTranslationParameters(),
                    'domain' => 'a_domain',
                    'locale' => 'a_locale',
                ],
                'locale' => ['a_locale'],
                'expectedTranslation' => 'Simple values',
            ],
            'Simple substitution' => [
                'message' => 'A %mood string',
                'givenOptionalConstructorArguments' => [
                    'parameters' => self::createTranslationParameters(['%mood' => 'happy']),
                ],
                'expectedTransArguments' => [
                    'message' => 'A %mood string',
                    'parameters' => self::createTranslationParameters(['%mood' => 'happy']),
                    'domain' => null,
                    'locale' => null,
                ],
                'locale' => [],
                'expectedTranslation' => 'A happy string',
            ],
            'Multiple substitutions' => [
                'message' => 'A %mood %size string',
                'givenOptionalConstructorArguments' => [
                    'parameters' => self::createTranslationParameters([
                        '%mood' => 'happy',
                        '%size' => 'little',
                    ]),
                ],
                'expectedTransArguments' => [
                    'message' => 'A %mood %size string',
                    'parameters' => self::createTranslationParameters([
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
