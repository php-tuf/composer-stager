<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests;

use PhpTuf\ComposerStager\Tests\TestUtils\AssertTrait;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemTestHelperTrait;
use PhpTuf\ComposerStager\Tests\TestUtils\FixtureTestHelperTrait;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelperTrait;
use PhpTuf\ComposerStager\Tests\TestUtils\PreconditionTestHelperTrait;
use PhpTuf\ComposerStager\Tests\TestUtils\TranslationTestHelperTrait;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;

abstract class TestCase extends PHPUnitTestCase
{
    use AssertTrait;
    use FilesystemTestHelperTrait;
    use FixtureTestHelperTrait;
    use PathTestHelperTrait;
    use PreconditionTestHelperTrait;
    use ProphecyTrait;
    use TranslationTestHelperTrait;
}
