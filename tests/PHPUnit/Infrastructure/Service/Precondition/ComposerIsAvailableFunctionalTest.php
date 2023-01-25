<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\LogicException;
use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinder;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinderInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ComposerIsAvailable;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Symfony\Component\DependencyInjection\Definition;

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * @coversNothing
 *
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $stagingDir
 * @property string $executableFinderClass
 */
final class ComposerIsAvailableFunctionalTest extends TestCase
{
    protected function setUp(): void
    {
        self::createTestEnvironment(self::ACTIVE_DIR);
        mkdir(self::STAGING_DIR, 0777, true);

        $this->activeDir = PathFactory::create(self::ACTIVE_DIR);
        $this->stagingDir = PathFactory::create(self::STAGING_DIR);
        $this->executableFinderClass = ExecutableFinder::class;
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    private function createSut(): ComposerIsAvailable
    {
        $container = $this->getContainer();

        // Override the ExecutableFinder implementation.
        $executableFinder = new Definition($this->executableFinderClass);
        $container->setDefinition(ExecutableFinderInterface::class, $executableFinder);

        // Compile the container.
        $container->compile();

        // Get services.
        /** @var \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ComposerIsAvailable $sut */
        $sut = $container->get(ComposerIsAvailable::class);

        return $sut;
    }

    // The happy path, which would usually have a test method here, is implicitly tested in the end-to-end test.
    // @see \PhpTuf\ComposerStager\Tests\PHPUnit\EndToEnd\EndToEndFunctionalTestCase

    public function testComposerNotFound(): void
    {
        $this->expectException(PreconditionException::class);
        $this->expectExceptionMessage(ComposerNotFoundExecutableFinder::EXCEPTION_MESSAGE);

        $this->executableFinderClass = ComposerNotFoundExecutableFinder::class;

        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

        self::assertFalse($isFulfilled, 'Correctly handled inability to find Composer.');

        $sut->assertIsFulfilled($this->activeDir, $this->stagingDir);
    }

    public function testInvalidComposerFound(): void
    {
        $this->expectException(PreconditionException::class);
        $this->expectExceptionMessage(InvalidComposerFoundExecutableFinder::getExceptionMessage());

        $this->executableFinderClass = InvalidComposerFoundExecutableFinder::class;

        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

        self::assertFalse($isFulfilled, 'Correctly handled invalid Composer executable.');

        $sut->assertIsFulfilled($this->activeDir, $this->stagingDir);
    }
}

final class ComposerNotFoundExecutableFinder implements ExecutableFinderInterface
{
    public const EXCEPTION_MESSAGE = 'Composer cannot be found.';

    public function find(string $name): string
    {
        throw new LogicException(self::EXCEPTION_MESSAGE);
    }
}

final class InvalidComposerFoundExecutableFinder implements ExecutableFinderInterface
{
    public function find(string $name): string
    {
        return __FILE__;
    }

    public static function getExceptionMessage(): string
    {
        return sprintf('The Composer executable at %s is invalid.', __FILE__);
    }
}
