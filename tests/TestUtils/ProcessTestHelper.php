<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

use PhpTuf\ComposerStager\Internal\SymfonyProcess\Value\Exception\ProcessFailedException as SymfonyProcessFailedException;
use PhpTuf\ComposerStager\Internal\SymfonyProcess\Value\Process as SymfonyProcess;
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

final class TestSymfonyProcess extends SymfonyProcess
{
    public function __construct(array $command = [])
    {
        parent::__construct($command);
    }

    public function isSuccessful(): bool
    {
        return true;
    }
}
