<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPStan\Rules\Interfaces;

use PhpParser\Node;
use PhpParser\Node\Stmt\Interface_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RuleErrorBuilder;
use PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface;
use PhpTuf\ComposerStager\Tests\PHPStan\Rules\AbstractRule;
use ReflectionClass;

/** Ensures that precondtion system diagrams stay current. */
final class PreconditionDiagramsInSyncRule extends AbstractRule
{
    private string $preconditionSystemHash;

    public function __construct(string $preconditionSystemHash, ReflectionProvider $reflectionProvider)
    {
        parent::__construct($reflectionProvider);

        $this->preconditionSystemHash = $preconditionSystemHash;
    }

    public function getNodeType(): string
    {
        return Interface_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $className = $this->getClassReflection($node)->getName();

        // Listen for PreconditionInterface simply as a convenient "hook" to run this rule.
        if ($className !== PreconditionInterface::class) {
            return [];
        }

        $classes = array_merge(get_declared_classes(), get_declared_interfaces());

        $hashes = [];

        foreach ($classes as $class) {
            $class = new ReflectionClass($class);
            $isPreconditionClass = $class->implementsInterface(PreconditionInterface::class);

            if (!$isPreconditionClass) {
                continue;
            }

            $hashes[] = hash_file('md5', $class->getFileName());
        }

        $hash = implode('', $hashes);
        $hash = hash('md5', $hash);

        if ($hash === $this->preconditionSystemHash) {
            return [];
        }

        $message = sprintf(
            'Precondition system classes have changed. Make sure the appropriate changes '
            . 'have been made to the diagrams in src/Domain/Service/Precondition/resources '
            . 'and update phpstan.neon.dist:parameters.preconditionSystemHash to %s',
            $hash,
        );

        return [RuleErrorBuilder::message($message)->build()];
    }
}
