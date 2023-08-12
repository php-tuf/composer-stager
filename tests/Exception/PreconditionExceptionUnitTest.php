<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Exception;

use Exception;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\Tests\Precondition\Service\TestPrecondition;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableMessage;

final class PreconditionExceptionUnitTest extends TestCase
{
    /** @covers \PhpTuf\ComposerStager\API\Exception\PreconditionException */
    public function testBasicFunctionality(): void
    {
        $message = 'Message';
        $translatableMessage = new TestTranslatableMessage($message);
        $code = 42;
        $previous = new Exception();
        $precondition = new TestPrecondition();
        $sut = new PreconditionException($precondition, $translatableMessage, $code, $previous);

        self::assertSame($precondition, $sut->getPrecondition(), 'Got precondition.');
        self::assertSame($message, $sut->getMessage(), 'Got untranslated message.');
        self::assertSame($translatableMessage, $sut->getTranslatableMessage(), 'Got translatable message.');
        self::assertSame($code, $sut->getCode(), 'Got code.');
        self::assertSame($previous, $sut->getPrevious(), 'Got previous exception.');
    }
}
