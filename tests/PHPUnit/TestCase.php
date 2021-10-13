<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit;

use Prophecy\PhpUnit\ProphecyTrait;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;

    protected const ACTIVE_DIR_DEFAULT = '/var/www/active';
    protected const STAGING_DIR_DEFAULT = '/var/www/staging';

    /**
     * Makes a path portable by ensuring directory separators match the OS.
     */
    protected static function fixSeparators(string $path)
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Makes paths portable by ensuring directory separators match the OS.
     */
    protected static function fixSeparatorsMultiple(&...$paths): void
    {
        foreach ($paths as &$path) {
            $path = self::fixSeparators($path);
        }
    }
}
