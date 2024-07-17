<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

use Symfony\Component\Process\Exception\ProcessFailedException as SymfonyProcessFailedException;
use Symfony\Component\Process\Process as SymfonyProcess;
use Throwable;

final class ProcessTestHelper
{
    /**
     * Creates a mock command array suitable for use with a Symfony Process.
     *
     * @param string $stdout
     *   The string to output to STDOUT.
     * @param string $stderr
     *   The string to output to STDERR. If provided, the command will return a non-zero status code.
     *
     * @see mock_command.php
     */
    public static function createMockCommand(string $stdout = '', string $stderr = ''): array
    {
        return [
            'php',
            __DIR__ . '/fwrite.php',
            '--stdout=' . $stdout,
            '--stderr=' . $stderr,
        ];
    }

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
