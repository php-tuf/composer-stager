<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPathList;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition */
final class AbstractPreconditionUnitTest extends PreconditionUnitTestCase
{
    protected const NAME = TestFulfilledPrecondition::NAME;
    protected const DESCRIPTION = TestFulfilledPrecondition::DESCRIPTION;

    protected function createSut(?string $class = TestFulfilledPrecondition::class): AbstractPrecondition
    {
        $environment = $this->environment->reveal();
        $translatableFactory = new TestTranslatableFactory();

        // Create a concrete implementation for testing since the SUT, being
        // abstract, can't be instantiated directly.
        return new $class($environment, $translatableFactory);
    }

    /**
     * @covers ::__construct
     * @covers ::assertIsFulfilled
     * @covers ::getDescription
     * @covers ::getFulfilledStatusMessage
     * @covers ::getLeaves
     * @covers ::getName
     * @covers ::getStatusMessage
     * @covers ::isFulfilled
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality(string $class, ?PathListInterface $exclusions, int $timeout): void
    {
        $activeDirPath = PathHelper::activeDirPath();
        $stagingDirPath = PathHelper::stagingDirPath();

        $sut = $this->createSut($class);

        self::assertEquals($class::NAME, $sut->getName()->trans());
        self::assertEquals($class::DESCRIPTION, $sut->getDescription()->trans());
        self::assertEquals($class::IS_FULFILLED, $sut->isFulfilled($activeDirPath, $stagingDirPath, $exclusions, $timeout));
        self::assertEquals($class::STATUS_MESSAGE, $sut->getStatusMessage($activeDirPath, $stagingDirPath, $exclusions, $timeout)->trans());
        self::assertEquals([$sut], $sut->getLeaves());
    }

    public function providerBasicFunctionality(): array
    {
        return [
            'Fulfilled, without exclusions' => [
                'class' => TestFulfilledPrecondition::class,
                'exclusions' => null,
                'timeout' => 10,
            ],
            'Unfulfilled, with exclusions' => [
                'class' => TestUnfulfilledPrecondition::class,
                'exclusions' => new TestPathList(),
                'timeout' => 100,
            ],
        ];
    }
}
