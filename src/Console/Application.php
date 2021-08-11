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
    private const VERSION = 'v0.1.1';

    public const ACTIVE_DIR_OPTION = 'active-dir';
    public const STAGING_DIR_OPTION = 'staging-dir';

    public const ACTIVE_DIR_DEFAULT = '.';
    public const STAGING_DIR_DEFAULT = '.composer_staging';

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);
    }

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    protected function getDefaultInputDefinition(): InputDefinition
    {
        $inputDefinition = parent::getDefaultInputDefinition();

        $inputDefinition->addOption(
            new InputOption(
                self::ACTIVE_DIR_OPTION,
                'd',
                InputOption::VALUE_REQUIRED,
                'Use the given directory as active directory',
                self::ACTIVE_DIR_DEFAULT
            )
        );

        $inputDefinition->addOption(
            new InputOption(
                self::STAGING_DIR_OPTION,
                's',
                InputOption::VALUE_REQUIRED,
                'Use the given directory as staging directory',
                self::STAGING_DIR_DEFAULT
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
