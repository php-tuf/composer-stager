<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Exception;

use Exception;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestDoubles\Precondition\Service\TestPrecondition;
use PhpTuf\ComposerStager\Tests\TestDoubles\Translation\Value\TestTranslatableMessage;
use Throwable;

final class PreconditionExceptionUnitTest extends TestCase
{
    /**
     * @covers \PhpTuf\ComposerStager\API\Exception\PreconditionException
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality(array $arguments, int $code, ?Throwable $previous): void
    {
        $sut = new PreconditionException(...array_values($arguments));

        self::assertSame($arguments['precondition'], $sut->getPrecondition(), 'Got precondition.');
        self::assertSame((string) $arguments['translatableMessage'], $sut->getMessage(), 'Got untranslated message.');
        self::assertSame($arguments['translatableMessage'], $sut->getTranslatableMessage(), 'Got translatable message.');
        self::assertSame($code, $sut->getCode(), 'Got code.');
        self::assertEquals($previous, $sut->getPrevious(), 'Got previous exception.');
    }

    public function providerBasicFunctionality(): array
    {
        return [
            'Minimum values' => [
                'arguments' => [
                    'precondition' => new TestPrecondition('one'),
                    'translatableMessage' => new TestTranslatableMessage('two'),
                ],
                'code' => 0,
                'previous' => null,
            ],
            'Simple values' => [
                'arguments' => [
                    'precondition' => new TestPrecondition('one'),
                    'translatableMessage' => new TestTranslatableMessage('two'),
                    'code' => 0,
                    'previous' => new Exception('three'),
                ],
                'code' => 0,
                'previous' => new Exception('three'),
            ],
        ];
    }
}
