<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface;
use PhpTuf\ComposerStager\Internal\Finder\Service\ExecutableFinder;
use PhpTuf\ComposerStager\Internal\Precondition\Service\ComposerIsAvailable;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\TranslationTestHelper;
use Symfony\Component\DependencyInjection\Definition;

/** @coversNothing */
final class ComposerIsAvailableFunctionalTest extends TestCase
{
    private string $executableFinderClass;

    protected function setUp(): void
    {
        self::createTestEnvironment();
        FilesystemTestHelper::createDirectories(PathTestHelper::stagingDirRelative());

        $this->executableFinderClass = ExecutableFinder::class;
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    private function createSut(): ComposerIsAvailable
    {
        $container = ContainerTestHelper::container();

        // Override the ExecutableFinder implementation.
        $executableFinder = new Definition($this->executableFinderClass);
        $container->setDefinition(ExecutableFinderInterface::class, $executableFinder);

        // Compile the container.
        $container->compile();

        // Get services.
        return $container->get(ComposerIsAvailable::class);
    }

    // The happy path, which would usually have a test method here, is implicitly tested in the end-to-end test.
    // @see \PhpTuf\ComposerStager\Tests\EndToEnd\EndToEndFunctionalTestCase

    public function testComposerNotFound(): void
    {
        $this->executableFinderClass = ComposerNotFoundExecutableFinder::class;
        $sut = $this->createSut();

        $message = ComposerNotFoundExecutableFinder::EXCEPTION_MESSAGE;
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->assertIsFulfilled(PathTestHelper::activeDirPath(), PathTestHelper::stagingDirPath());
        }, PreconditionException::class, $message, null, LogicException::class);
    }

    public function testInvalidComposerFound(): void
    {
        $this->executableFinderClass = InvalidComposerFoundExecutableFinder::class;
        $sut = $this->createSut();

        $message = InvalidComposerFoundExecutableFinder::getExceptionMessage();
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->assertIsFulfilled(PathTestHelper::activeDirPath(), PathTestHelper::stagingDirPath());
        }, PreconditionException::class, $message);
    }
}

final class ComposerNotFoundExecutableFinder implements ExecutableFinderInterface
{
    public const EXCEPTION_MESSAGE = 'Cannot find Composer.';

    public function find(string $name): string
    {
        throw new LogicException(TranslationTestHelper::createTranslatableMessage(self::EXCEPTION_MESSAGE));
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
