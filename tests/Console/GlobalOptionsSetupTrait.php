<?php

namespace PhpTuf\ComposerStager\Tests\Console;

use PhpTuf\ComposerStager\Console\GlobalOptions;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @property \PhpTuf\ComposerStager\Console\GlobalOptions|\Prophecy\Prophecy\ObjectProphecy $globalOptions
 */
trait GlobalOptionsSetupTrait
{
    use ProphecyTrait;

    protected function setUpGlobalOptions(): void
    {
        $this->globalOptions = $this->prophesize(GlobalOptions::class);
        $this->globalOptions
            ->getDefaultActiveDir()
            ->willReturn('');
        $this->globalOptions
            ->getDefaultStagingDir()
            ->willReturn('');
        $this->globalOptions
            ->resolveActiveDir(Argument::any())
            ->willReturnArgument();
        $this->globalOptions
            ->resolveActiveDir(null)
            ->willReturn('');
        $this->globalOptions
            ->resolveStagingDir(Argument::any())
            ->willReturnArgument();
        $this->globalOptions
            ->resolveStagingDir(null)
            ->willReturn('');
    }
}
