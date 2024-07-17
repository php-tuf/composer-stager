<?php declare(strict_types=1);

/**
 * @file Provides a script for writing to STDOUT and STDERR for testing.
 *
 * Usage:
 *   ```php
 *   php fwrite.php [options]
 *   --stdout A string to output to STDOUT [default: ""]
 *   --stderr A string to output to STDERR [default: ""]
 *   ```
 */

$options = getopt('', ['stdout::', 'stderr::']);

fwrite(STDOUT, $options['stdout'] ?? '');

if (isset($options['stderr'])) {
    fwrite(STDERR, $options['stderr']);
}
