<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;
use PhpTuf\ComposerStager\Tests\TestUtils\PreconditionTestHelper as Helper;

/**
 * Provides convenience methods for PreconditionTestHelper calls.
 *
 * @see \PhpTuf\ComposerStager\Tests\TestUtils\PreconditionTestHelper
 */
trait PreconditionTestHelperTrait
{
    public static function createTestPreconditionException(
        string $message = '',
        ?TranslationParametersInterface $parameters = null,
    ): PreconditionException {
        return Helper::createTestPreconditionException($message, $parameters);
    }
}
