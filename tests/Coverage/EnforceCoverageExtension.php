<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Coverage;

use PHPUnit\Event\Application\Finished;
use PHPUnit\Event\Application\FinishedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use SimpleXMLElement;
use Throwable;

/**
 * Enforces the configured code coverage requirement.
 *
 * @see ../../phpunit.xml.dist Search for "required-coverage"
 */
final class EnforceCoverageExtension implements Extension, FinishedSubscriber
{
    private readonly Configuration $configuration;
    private readonly ParameterCollection $parameters;

    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $this->configuration = $configuration;
        $this->parameters = $parameters;
        $facade->registerSubscriber($this);
    }

    public function notify(Finished $event): void
    {
        if (!$this->isRequiredTestSuite()) {
            $this->notice(sprintf(
                'The "%s" test suite does not enforce a test coverage requirement.',
                $this->configuration->includeTestSuite(),
            ));

            return;
        }

        if (!$this->isCoverageEnabled()) {
            $this->notice('Code coverage is disabled.');

            return;
        }

        $coverageNumber = $this->getCoverageNumber();
        $requiredCoverage = (int) $this->parameters->get('required-coverage');

        if ($coverageNumber >= $requiredCoverage) {
            $this->ok(sprintf(
                'Code coverage meets the required %d%% (%d%%).',
                $requiredCoverage,
                $coverageNumber,
            ));

            return;
        }

        $this->fail(
            sprintf(
                'Code coverage is below the required %d%% (%d%%).',
                $requiredCoverage,
                $coverageNumber,
            ),
        );
    }

    private function isCoverageEnabled(): bool
    {
        return function_exists('xdebug_info')
            && $this->configuration->hasCoverageClover();
    }

    private function isRequiredTestSuite(): bool
    {
        $activeTestSuite = $this->configuration->includeTestSuite();
        $requiredTestSuites = explode(',', $this->parameters->get('required-testsuites'));

        return in_array($activeTestSuite, $requiredTestSuites, true);
    }

    private function getCoverageNumber(): int
    {
        $filename = $this->configuration->coverageClover();

        $contents = file_get_contents($filename);

        if ($contents === false) {
            $this->fail(sprintf('Code coverage data is missing at %s.', $filename));
        }

        try {
            $xml = new SimpleXMLElement($contents);
        } catch (Throwable) {
            $this->fail(sprintf('Code coverage data is corrupt at %s.', $filename));
        }

        $totalElements = (int) current($xml->xpath('/coverage/project/metrics/@elements') ?? []);
        $coveredElements = (int) current($xml->xpath('/coverage/project/metrics/@coveredelements') ?? []);

        return $totalElements > 0
            ? (int) floor($coveredElements / $totalElements * 100)
            : 0;
    }

    private function notice(string $message): void
    {
        $this->print("[NOTICE] {$message}");
    }

    private function ok(string $message): void
    {
        $green = "\033[42;30m";
        $this->print("[OK] {$message}", $green);
    }

    private function fail(string $message): never
    {
        $red = "\033[41;30m";
        $this->print("[FAIL] {$message}", $red);

        exit(1);
    }

    private function print(string $message, ?string $backgroundColor = null): void
    {
        if ($this->configuration->noOutput()) {
            return;
        }

        $message = $this->configuration->colors() && $backgroundColor
            ? $backgroundColor . $message . "\033[0m"
            : $message;

        print PHP_EOL . $message . PHP_EOL;
    }
}
