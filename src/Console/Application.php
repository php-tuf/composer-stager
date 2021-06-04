<?php

namespace PhpTuf\ComposerStager\Console;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
final class Application extends \Symfony\Component\Console\Application
{
    private const NAME = 'Composer Stager';
    private const VERSION = 'v1.0.x-dev';

    /**
     * @var \PhpTuf\ComposerStager\Console\GlobalOptionsInterface
     */
    private $globalOptions;

    public function __construct(GlobalOptionsInterface $globalOptions)
    {
        parent::__construct(self::NAME, self::VERSION);
        $this->globalOptions = $globalOptions;
    }

    /**
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\LogicException
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
