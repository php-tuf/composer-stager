<?php declare(strict_types=1);

/**
 * @file This is for debugging PHPBench benchmark hooks, because PHPBench
 *     itself doesn't provide very helpful feedback when they fail.
 *
 * @see https://phpbench.readthedocs.io/en/latest/annotributes.html#id4
 */

use PhpTuf\ComposerStager\PHPBench\Benchmark\BenchCase;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

BenchCase::setUpBeforeClass();
