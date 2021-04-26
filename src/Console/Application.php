<?php

namespace PhpTuf\ComposerStager\Console;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends \Symfony\Component\Console\Application
{
    private const NAME = 'Composer Stager';
    private const VERSION = 'v1.0.x-dev';

    /**
     * @var \PhpTuf\ComposerStager\Console\GlobalOptions
     */
    private $globalOptions;

    public function __construct(GlobalOptions $globalOptions)
    {
        parent::__construct(static::NAME, static::VERSION);
        $this->globalOptions = $globalOptions;
    }

    /**
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     */
    protected function getDefaultInputDefinition(): InputDefinition
    {
        $inputDefinition = parent::getDefaultInputDefinition();

        $inputDefinition->addOption(
            new InputOption(
                GlobalOptions::ACTIVE_DIR,
                'd',
                InputOption::VALUE_REQUIRED,
                'Use the given directory as active directory',
                $this->globalOptions->getDefaultActiveDir()
            )
        );

        $inputDefinition->addOption(
            new InputOption(
                GlobalOptions::STAGING_DIR,
                's',
                InputOption::VALUE_REQUIRED,
                'Use the given directory as staging directory',
                $this->globalOptions->getDefaultStagingDir()
            )
        );

        return $inputDefinition;
    }

    protected function configureIO(InputInterface $input, OutputInterface $output): void
    {
        $output->getFormatter()->setStyle(
            'error',
            // Red foreground, no background.
            new OutputFormatterStyle('red')
        );
    }
}
