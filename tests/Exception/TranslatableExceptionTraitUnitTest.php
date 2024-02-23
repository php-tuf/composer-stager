<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Exception;

use Exception;
use PhpTuf\ComposerStager\API\Exception\TranslatableExceptionTrait;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Tests\TestCase;

/** @coversDefaultClass \PhpTuf\ComposerStager\API\Exception\TranslatableExceptionTrait */
final class TranslatableExceptionTraitUnitTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getTranslatableMessage
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality(array $arguments, TranslatableInterface $message, int $code): void
    {
        $sut = new class(...$arguments) extends Exception {
            use TranslatableExceptionTrait;
        };

        self::assertSame((string) $message, $sut->getMessage(), 'Returned correct untranslated message.');
        self::assertEquals($message, $sut->getTranslatableMessage(), 'Returned correct translatable message.');
        self::assertSame($code, $sut->getCode(), 'Returned correct code.');
    }

    public function providerBasicFunctionality(): array
    {
        return [
            [
                'arguments' => [
                    self::createTranslatableMessage('One'),
                ],
                'message' => self::createTranslatableMessage('One'),
                'code' => 0,
            ],
            [
                'arguments' => [
                    self::createTranslatableMessage('Two'),
                    10,
                ],
                'message' => self::createTranslatableMessage('Two'),
                'code' => 10,
            ],
        ];
    }
}
