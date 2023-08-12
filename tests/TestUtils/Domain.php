<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

final class Domain
{
    public const DEFAULT = 'messages';
    public const EXCEPTIONS = 'exceptions';

    private function __construct()
    {
        // Prevent instantiation.
    }
}
