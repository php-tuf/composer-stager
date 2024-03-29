<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPStan\Rules\Interfaces;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Interface_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\PHPStan\Rules\AbstractRule;
use ReflectionClass;

/** Ensures that translation system diagrams stay current. */
final class TranslationDiagramsInSyncRule extends AbstractRule
{
    public function __construct(private readonly string $preconditionSystemHash, ReflectionProvider $reflectionProvider)
    {
        parent::__construct($reflectionProvider);
    }

    public function getNodeType(): string
    {
        return ClassLike::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        // Only check classes and interfaces.
        if (!$node instanceof Class_ && !$node instanceof Interface_) {
            return [];
        }

        $class = $this->getClassReflection($node);

        if (!$class instanceof ClassReflection) {
            return [];
        }

        // Listen for TranslatableInterface simply as a convenient "hook" to run this rule.
        if ($class->getName() !== TranslatableInterface::class) {
            return [];
        }

        $hashes = [];

        foreach ($this->getTranslationFilesFilenames() as $filename) {
            $hashes[] = hash_file('md5', $filename);
        }

        $hash = implode('', $hashes);
        $hash = hash('md5', $hash);

        if ($hash === $this->preconditionSystemHash) {
            return [];
        }

        return [
            $this->buildErrorMessage(sprintf(
                'Translation system classes have changed. Make sure the '
                . 'appropriate changes have been made to the diagrams in docs/translation '
                . "(don't forget to export and optimize the images) and update "
                . 'phpstan.neon.dist:parameters.translationSystemHash to %s',
                $hash,
            )),
        ];
    }

    private function getTranslationFilesFilenames(): array
    {
        $filenames = [];

        foreach ($this->getClassMap() as $name => $filename) {
            // Limit to Composer Stager production code.
            if (!str_contains((string) $filename, '/../../src/')) {
                continue;
            }

            $class = new ReflectionClass($name);
            $namespace = $class->getName();

            // Limit to translation package.
            $isProductionTranslationClass = $this->isInNamespace(
                $namespace,
                'PhpTuf\\ComposerStager\\API\\Translation\\',
            );
            $isInternalTranslationClass = $this->isInNamespace(
                $namespace,
                'PhpTuf\\ComposerStager\\Internal\\Translation\\',
            );

            if (!$isProductionTranslationClass && !$isInternalTranslationClass) {
                continue;
            }

            $filenames[] = $class->getFileName();
        }

        return $filenames;
    }
}
