<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

use PhpTuf\ComposerStager\Tests\TestDoubles\Process\Service\TestSymfonyProcess;
use Symfony\Component\Process\Exception\ProcessFailedException as SymfonyProcessFailedException;
use Throwable;

final class ProcessTestHelper
{
    /** Creates a SymfonyProcessFailedException for use as a $previous value in test prophesies. */
    public static function createSymfonyProcessFailedException(): Throwable
    {
        try {
            return new SymfonyProcessFailedException(
                new TestSymfonyProcess(),
            );
        } catch (Throwable $e) {
            return $e;
        }
    }
}
