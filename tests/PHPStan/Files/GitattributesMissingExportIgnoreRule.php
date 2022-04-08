<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPStan\Files;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\RuleErrorBuilder;
use PhpTuf\ComposerStager\Tests\PHPStan\AbstractRule;

/**
 * Ensures that a conscious decision is made about whether to include new repository root paths in Git archive files.
 */
final class GitattributesMissingExportIgnoreRule extends AbstractRule
{
    // Paths that are included in archive files, i.e., not excluded by .gitattributes.
    private const INCLUDED_PATHS = [
        'composer.json',
        'config',
        'docs',
        'LICENSE',
        'src',
        'tests',
        'vendor',
    ];

    private const SPECIAL_PATHS = [
        '.',
        '..',
        '.DS_Store',
        '.git',
    ];

    public function getNodeType(): string
    {
        return FileNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $filename = explode(DIRECTORY_SEPARATOR, $scope->getFile());
        $filename = array_pop($filename);

        if ($filename !== '.gitattributes') {
            return [];
        }

        $errors = [];

        $rootPaths = scandir(__DIR__ . '/../../../');

        foreach ($rootPaths as $rootPath) {
            if (in_array($rootPath, self::SPECIAL_PATHS, true)) {
                continue;
            }

            if ($this->isIncluded($rootPath) || $this->isExcluded($rootPath)) {
                continue;
            }

            $message = "Repository root path /{$rootPath} must be either defined as \"export-ignore\" in .gitattributes or declared in \PhpTuf\ComposerStager\Tests\PHPStan\Files\GitattributesMissingExportIgnoreRule::INCLUDED_PATHS";
            $errors[] = RuleErrorBuilder::message($message)->build();
        }

        return $errors;
    }

    /**
     * Determines whether the given filename is included in archive files, i.e.,
     * is not excluded by .gitattributes.
     */
    private function isIncluded(string $filename): bool
    {
        return in_array($filename, self::INCLUDED_PATHS, true);
    }

    /**
     * Determines whether the given filename is excluded from archive files by .gitattributes.
     */
    private function isExcluded(string $filename): bool
    {
        $gitattributes = file(__DIR__ . '/../../../.gitattributes');
        $gitattributes = array_map(static function ($value) {
            $value = ltrim($value, DIRECTORY_SEPARATOR);
            preg_match('/^(.*)\s*export-ignore$/', $value, $matches);
            return trim($matches[1]);
        }, $gitattributes);
        $gitattributes = array_filter($gitattributes);
        return in_array($filename, $gitattributes, true);
    }
}
