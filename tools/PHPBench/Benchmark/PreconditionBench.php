<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPBench\Benchmark;

use Generator;
use PhpBench\Attributes\BeforeClassMethods;
use PhpBench\Attributes\ParamProviders;
use PhpTuf\ComposerStager\API\Precondition\Service\PreconditionInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPreconditionsTree;
use PhpTuf\ComposerStager\Internal\Precondition\Service\BeginnerPreconditions;
use PhpTuf\ComposerStager\Internal\Precondition\Service\CleanerPreconditions;
use PhpTuf\ComposerStager\Internal\Precondition\Service\CommitterPreconditions;
use PhpTuf\ComposerStager\Internal\Precondition\Service\StagerPreconditions;
use PhpTuf\ComposerStager\PHPBench\TestUtils\FixtureHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerTestHelper;

#[BeforeClassMethods(['setUpBeforeClass'])]
final class PreconditionBench extends BenchCase
{
    #[ParamProviders(['providerCoreTrees'])]
    public function benchCoreTrees(array $params): void
    {
        $this->doBench($params);
    }

    /** Provides the preconditions for the top level, core services. */
    public function providerCoreTrees(): Generator
    {
        yield 'BeginnerPreconditions' => [BeginnerPreconditions::class];
        yield 'CommitterPreconditions' => [CommitterPreconditions::class];
        yield 'StagerPreconditions' => [StagerPreconditions::class];
        yield 'CleanerPreconditions' => [CleanerPreconditions::class];
    }

    #[ParamProviders(['providerLeaves'])]
    public function benchLeaves(array $params): void
    {
        $this->doBench($params);
    }

    public function providerLeaves(): Generator
    {
        $container = ContainerTestHelper::container();
        $services = $container->getDefinitions();

        foreach ($services as $definition) {
            $classFqn = $definition->getClass();

            if (!is_string($classFqn) || !class_exists($classFqn)) {
                continue;
            }

            // Target preconditions.
            if (!is_a($classFqn, PreconditionInterface::class, true)) {
                continue;
            }

            // Target leaves.
            if (is_subclass_of($definition->getClass(), AbstractPreconditionsTree::class)) {
                continue;
            }

            $fqnParts = explode('\\', $classFqn);
            $className = array_pop($fqnParts);

            yield $className => [$classFqn];
        }
    }

    private function doBench(array $params): void
    {
        $sut = $this->container->get(reset($params));
        assert($sut instanceof PreconditionInterface);

        $activeDir = FixtureHelper::drupalOriginalCodebasePath();
        $stagingDir = FixtureHelper::drupalMajorUpdateCodebasePath();

        $sut->isFulfilled($activeDir, $stagingDir);
    }
}
