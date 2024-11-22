<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\ComposerIsAvailable;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\TranslationTestHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\Definition;

#[CoversClass(ComposerIsAvailable::class)]
final class ComposerIsAvailableFunctionalTest extends TestCase
{
    protected function setUp(): void
    {
        self::createTestEnvironment();
        self::mkdir(self::stagingDirRelative());
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    private function createSut(?string $executableFinderClass = null): ComposerIsAvailable
    {
        if ($executableFinderClass === null) {
            return ContainerTestHelper::get(ComposerIsAvailable::class);
        }

        $container = ContainerTestHelper::container();

        // Override the ExecutableFinder implementation.
        $executableFinder = new Definition($executableFinderClass);
        $container->setDefinition(ExecutableFinderInterface::class, $executableFinder);

        // Compile the container.
        $container->compile();

        // Get services.
        return $container->get(ComposerIsAvailable::class);
    }

    public function testFulfilled(): void
    {
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(self::activeDirPath(), self::stagingDirPath());
        $isStillFulfilled = $sut->isFulfilled(self::activeDirPath(), self::stagingDirPath());

        self::assertTrue($isFulfilled, 'Found Composer.');
        self::assertTrue($isStillFulfilled, 'Achieved idempotency');
    }

    public function testComposerNotFound(): void
    {
        $sut = $this->createSut(ComposerNotFoundExecutableFinder::class);

        $message = 'Cannot find Composer.';
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->assertIsFulfilled(self::activeDirPath(), self::stagingDirPath());
        }, PreconditionException::class, $message, null, LogicException::class);
    }

    #[DataProvider('providerInvalidComposerFound')]
    public function testInvalidComposerFound(string $output): void
    {
        $sut = $this->createSut(InvalidComposerFoundExecutableFinder::class);

        // Dynamically set invalid executable output.
        $reflection = new ReflectionProperty($sut, 'executableFinder');
        /** @var \PhpTuf\ComposerStager\Tests\Precondition\Service\InvalidComposerFoundExecutableFinder $executableFinder */
        $executableFinder = $reflection->getValue($sut);
        $executableFinder->output = $output;

        $message = InvalidComposerFoundExecutableFinder::getExceptionMessage();
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->assertIsFulfilled(self::activeDirPath(), self::stagingDirPath());
        }, PreconditionException::class, $message);
    }

    public static function providerInvalidComposerFound(): array
    {
        return [
            'No output' => [''],
            'Non-JSON' => ['invalid'],
            'Empty JSON/missing application name' => ['{}'],
            'Wrong application name' => ['{"application":{"name":"Invalid"}}'],
        ];
    }
}

final class ComposerNotFoundExecutableFinder implements ExecutableFinderInterface
{
    public function find(string $name): string
    {
        throw new LogicException(TranslationTestHelper::createTranslatableMessage(''));
    }
}

final class InvalidComposerFoundExecutableFinder implements ExecutableFinderInterface
{
    public string $output = '';

    public function find(string $name): string
    {
        $executable = self::executablePath();

        file_put_contents($executable, <<<END
            #!/usr/bin/env bash
            echo '{$this->output}'
            END);
        FilesystemTestHelper::chmod($executable, 0777);

        return $executable;
    }

    public static function getExceptionMessage(): string
    {
        return sprintf('The Composer executable at %s is invalid.', self::executablePath());
    }

    private static function executablePath(): string
    {
        return PathTestHelper::makeAbsolute('composer');
    }
}
