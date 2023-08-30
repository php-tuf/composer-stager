<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

/** Provides common data providers. */
trait ProviderTrait
{
    /** Process timeout values. */
    public function providerTimeouts(): array
    {
        return [
            'Positive number' => [30],
            'Zero' => [0],
            'Negative number' => [-30],
        ];
    }
}
