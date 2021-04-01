<?php

namespace PhpTuf\ComposerStager\Console;

use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

class Application extends SymfonyApplication
{
    private const NAME = 'Composer Stager';
    private const VERSION = 'v1.0.x-dev';

    public function __construct()
    {
        parent::__construct(static::NAME, static::VERSION);
    }

    protected function getDefaultInputDefinition(): InputDefinition
    {
        $inputDefinition = parent::getDefaultInputDefinition();

        $inputDefinition->addOption(
            new InputOption(
                'active-dir',
                'd',
                InputOption::VALUE_REQUIRED,
                'Use the given directory as active directory'
            )
        );

        $inputDefinition->addOption(
            new InputOption(
                'staging-dir',
                's',
                InputOption::VALUE_REQUIRED,
                'Use the given directory as staging directory'
            )
        );

        return $inputDefinition;
    }
}
