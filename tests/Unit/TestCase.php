<?php

namespace PhpTuf\ComposerStager\Tests\Unit;

use Prophecy\PhpUnit\ProphecyTrait;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;

    protected const ACTIVE_DIR_DEFAULT = '/var/www/active';
    protected const STAGING_DIR_DEFAULT = '/var/www/staging';
}
