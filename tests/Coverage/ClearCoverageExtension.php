<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Coverage;

use FilesystemIterator;
use PHPUnit\Event\TestRunner\ExecutionStarted;
use PHPUnit\Event\TestRunner\ExecutionStartedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/** Clears any code coverage data from previous runs. */
final class ClearCoverageExtension implements Extension, ExecutionStartedSubscriber
{
    private Configuration $configuration;

    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $this->configuration = $configuration;
        $facade->registerSubscriber($this);
    }

    public function notify(ExecutionStarted $event): void
    {
        if (!$this->isCoverageEnabled()) {
            return;
        }

        $cloverFile = $this->configuration->coverageClover();
        $cloverDir = dirname($cloverFile);

        if (!is_dir($cloverDir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($cloverDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var \SplFileInfo $item */
        foreach ($iterator as $item) {
            if ($item->getFilename() === '.gitkeep') {
                continue;
            }

            if ($item->isFile() || $item->isLink()) {
                @unlink($item->getPathname());
            } elseif ($item->isDir()) {
                @rmdir($item->getPathname());
            }
        }
    }

    private function isCoverageEnabled(): bool
    {
        return function_exists('xdebug_info')
            && $this->configuration->hasCoverageClover();
    }
}
