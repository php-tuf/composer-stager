<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestDoubles;

/** @see http://xunitpatterns.com/Test%20Spy.html */
interface TestSpyInterface
{
    /** @phpstan-ignore-next-line */
    public function report(...$params);
}
