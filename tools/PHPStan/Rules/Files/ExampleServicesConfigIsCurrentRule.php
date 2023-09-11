<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPStan\Rules\Files;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PhpTuf\ComposerStager\PHPStan\Rules\AbstractRule;
use Symfony\Component\Filesystem\Path as SymfonyPath;

/** Ensures that the example docs/services.yml is current. */
final class ExampleServicesConfigIsCurrentRule extends AbstractRule
{
    private const EXAMPLE_CONFIG_FILE = '/docs/services.yml';
    private const PROJECT_CONFIG_FILE = '/config/services.yml';

    public function getNodeType(): string
    {
        return FileNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $filename = SymfonyPath::canonicalize($scope->getFile());

        // Target example services configuration.
        if (!str_ends_with($filename, self::EXAMPLE_CONFIG_FILE)) {
            return [];
        }

        $exampleConfigFile = $filename;
        $projectConfigFile = str_replace(self::EXAMPLE_CONFIG_FILE, self::PROJECT_CONFIG_FILE, $exampleConfigFile);

        // Get example config.
        $exampleConfig = file_get_contents($exampleConfigFile);

        // Convert paths for comparison.
        $exampleConfig = str_replace('../vendor/php-tuf/composer-stager/src/', '../src/', $exampleConfig);

        // Get example config.
        $projectConfig = file_get_contents($projectConfigFile);

        // Strip comments.
        $exampleConfig = trim(preg_replace('/ *#.*\n/', '', $exampleConfig));
        $projectConfig = trim(preg_replace('/ *#.*\n/', '', $projectConfig));

        if ($exampleConfig === $projectConfig) {
            return [];
        }

        return [
            $this->buildErrorMessage(
                sprintf(
                    '%s and %s have diverged. Has there been '
                    . 'a BC break, such as adding a new dependency?',
                    self::EXAMPLE_CONFIG_FILE,
                    self::PROJECT_CONFIG_FILE,
                )
                . PHP_EOL . PHP_EOL
                . self::EXAMPLE_CONFIG_FILE . ':' . PHP_EOL
                . $this->formatDebugOutput($exampleConfig)
                . PHP_EOL . PHP_EOL
                . '------------------------------------------------------------'
                . PHP_EOL . PHP_EOL
                . self::PROJECT_CONFIG_FILE . ':' . PHP_EOL
                . $this->formatDebugOutput($projectConfig)
                . PHP_EOL,
            ),
        ];
    }

    private function formatDebugOutput(string $string): string
    {
        return str_replace(' ', '·', $string);
    }
}
