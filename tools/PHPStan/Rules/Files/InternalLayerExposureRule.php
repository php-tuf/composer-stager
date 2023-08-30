<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPStan\Rules\Files;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PhpTuf\ComposerStager\PHPStan\Rules\AbstractRule;

/** Ensures that a client autoloaders don't depend on the Internal layer. */
final class InternalLayerExposureRule extends AbstractRule
{
    private const FILENAME = '/config/services.yml';

    public function getNodeType(): string
    {
        return FileNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $filename = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $scope->getFile());

        // Target services configuration.
        if (!str_ends_with($filename, self::FILENAME)) {
            return [];
        }

        $contents = file_get_contents($filename);

        if (str_contains($contents, 'PhpTuf\\ComposerStager\\Internal\\')) {
            return [
                $this->buildErrorMessage(
                    'Service autoloading depends on the Internal layer, i.e., '
                    . 'explicitly refers to PhpTuf\\ComposerStager\\Internal.',
                ),
            ];
        }

        return [];
    }
}
