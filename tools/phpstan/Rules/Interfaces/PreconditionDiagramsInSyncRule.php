<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPStan\Rules\Interfaces;

use Composer\Autoload\ClassLoader;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Interface_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PhpTuf\ComposerStager\API\Precondition\Service\PreconditionInterface;
use PhpTuf\ComposerStager\PHPStan\Rules\AbstractRule;
use ReflectionClass;

/** Ensures that precondtion system diagrams stay current. */
final class PreconditionDiagramsInSyncRule extends AbstractRule
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

        // Listen for PreconditionInterface simply as a convenient "hook" to run this rule.
        if ($class->getName() !== PreconditionInterface::class) {
            return [];
        }

        $hashes = [];

        foreach ($this->getPreconditionsFilenames() as $filename) {
            $hashes[] = hash_file('md5', $filename);
        }

        $hash = implode('', $hashes);
        $hash = hash('md5', $hash);

        if ($hash === $this->preconditionSystemHash) {
            return [];
        }

        return [
            $this->buildErrorMessage(sprintf(
                'Precondition system classes have changed. Make sure the '
                . 'appropriate changes have been made to the diagrams in docs/preconditions '
                . "(don't forget to export and optimize the images) and update "
                . 'phpstan.neon.dist:parameters.preconditionSystemHash to %s',
                $hash,
            )),
        ];
    }

    private function getPreconditionsFilenames(): array
    {
        $filenames = [];

        foreach ($this->getClassMap() as $name => $filename) {
            // Limit to Composer Stager production code.
            if (!str_contains((string) $filename, '/../../src/')) {
                continue;
            }

            $class = new ReflectionClass($name);

            // Limit to preconditions.
            if (!$class->implementsInterface(PreconditionInterface::class)) {
                continue;
            }

            $filenames[] = $class->getFileName();
        }

        return $filenames;
    }

    /**
     * You would think get_declared_classes() and get_declared_interfaces() would provide
     * the needed list of Composer Stager symbols, but for some reason they only include
     * its PHPStan rules. This approach gets everything Composer knows about.
     */
    private function getClassMap(): array
    {
        $autoloader = require dirname(__DIR__, 3) . '/../vendor/autoload.php';
        assert($autoloader instanceof ClassLoader);

        return $autoloader->getClassMap();
    }
}
