<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Filesystem\Service;

use PhpTuf\ComposerStager\API\Environment\Service\EnvironmentInterface;
use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestDoubles\Process\Service\TestOutputCallback;
use PhpTuf\ComposerStager\Tests\TestDoubles\TestSpyInterface;
use PhpTuf\ComposerStager\Tests\TestUtils\BuiltinFunctionMocker;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\TranslationTestHelper;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Filesystem\Exception\IOException as SymfonyIOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem
 *
 * @covers \PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem::__construct
 */
final class FilesystemUnitTest extends TestCase
{
    private EnvironmentInterface|ObjectProphecy $environment;
    private PathFactoryInterface|ObjectProphecy $pathFactory;
    private SymfonyFilesystem|ObjectProphecy $symfonyFilesystem;

    protected function setUp(): void
    {
        $this->environment = $this->prophesize(EnvironmentInterface::class);
        $this->environment->setTimeLimit(Argument::type('integer'))
            ->willReturn(true);
        $this->pathFactory = $this->prophesize(PathFactoryInterface::class);
        $this->symfonyFilesystem = $this->prophesize(SymfonyFilesystem::class);
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    private function createSut(): Filesystem
    {
        $environment = $this->environment->reveal();
        $pathFactory = $this->pathFactory->reveal();
        $symfonyFilesystem = $this->symfonyFilesystem->reveal();
        $translatableFactory = TranslationTestHelper::createTranslatableFactory();

        return new Filesystem($environment, $pathFactory, $symfonyFilesystem, $translatableFactory);
    }

    /**
     * @covers ::chmod
     *
     * @group no_windows
     * @runInSeparateProcess
     */
    public function testChmodFailure(): void
    {
        $permissions = 777;
        $pathAbsolute = PathTestHelper::arbitraryFileAbsolute();
        $this->symfonyFilesystem
            ->exists(Argument::any())
            ->willReturn(true);
        BuiltinFunctionMocker::mock([
            'chmod' => $this->prophesize(TestSpyInterface::class),
            'file_exists' => $this->prophesize(TestSpyInterface::class),
        ]);
        BuiltinFunctionMocker::$spies['chmod']
            ->report($pathAbsolute, $permissions)
            ->shouldBeCalledOnce()
            ->willReturn(false);
        BuiltinFunctionMocker::$spies['file_exists']
            ->report($pathAbsolute)
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $sut = $this->createSut();

        $message = sprintf('The file mode could not be changed on %s.', $pathAbsolute);
        self::assertTranslatableException(static function () use ($sut, $permissions): void {
            $sut->chmod(PathTestHelper::arbitraryFilePath(), $permissions);
        }, IOException::class, $message);
    }

    /**
     * @covers ::copy
     *
     * @runInSeparateProcess
     */
    public function testCopyPermissionsFailure(): void
    {
        $this->pathFactory
            ->create(Argument::cetera())
            ->willReturn(PathTestHelper::createPath(PathTestHelper::arbitraryFileAbsolute()));
        BuiltinFunctionMocker::mock([
            'chmod' => $this->prophesize(TestSpyInterface::class),
            'copy' => $this->prophesize(TestSpyInterface::class),
            'file_exists' => $this->prophesize(TestSpyInterface::class),
            'fileperms' => $this->prophesize(TestSpyInterface::class),
        ]);
        BuiltinFunctionMocker::$spies['chmod']
            ->report(Argument::cetera())
            ->willReturn(false);
        BuiltinFunctionMocker::$spies['copy']
            ->report(Argument::cetera())
            ->willReturn(true);
        BuiltinFunctionMocker::$spies['file_exists']
            ->report(Argument::cetera())
            ->willReturn(true);
        BuiltinFunctionMocker::$spies['fileperms']
            ->report(Argument::cetera())
            ->willReturn(12_345);
        $sut = $this->createSut();

        $message = sprintf('The file mode could not be changed on %s.', PathTestHelper::destinationDirAbsolute());
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->copy(PathTestHelper::activeDirPath(), PathTestHelper::destinationDirPath());
        }, IOException::class, $message);
    }

    /**
     * @covers ::assertCopyPreconditions
     * @covers ::copy
     */
    public function testCopyDirectoriesTheSame(): void
    {
        $samePath = PathTestHelper::activeDirPath();
        $sut = $this->createSut();

        $message = sprintf('The source and destination files cannot be the same at %s', $samePath->absolute());
        self::assertTranslatableException(static function () use ($sut, $samePath): void {
            $sut->copy($samePath, $samePath);
        }, LogicException::class, $message);
    }

    /**
     * @covers ::fileMode
     *
     * @runInSeparateProcess
     */
    public function testFileModeFailure(): void
    {
        $path = PathTestHelper::arbitraryFilePath();
        FilesystemTestHelper::touch($path->absolute());
        BuiltinFunctionMocker::mock(['fileperms' => $this->prophesize(TestSpyInterface::class)]);
        BuiltinFunctionMocker::$spies['fileperms']
            ->report($path->absolute())
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $this->symfonyFilesystem
            ->exists(Argument::any())
            ->willReturn(true);
        $sut = $this->createSut();

        $message = sprintf('Failed to get permissions on path at %s', $path->absolute());
        self::assertTranslatableException(static function () use ($sut, $path): void {
            $sut->fileMode($path);
        }, IOException::class, $message);
    }

    /**
     * @covers ::isWritable
     *
     * @dataProvider providerIsWritable
     *
     * @runInSeparateProcess
     */
    public function testIsWritable(bool $expected): void
    {
        BuiltinFunctionMocker::mock(['is_writable' => $this->prophesize(TestSpyInterface::class)]);
        BuiltinFunctionMocker::$spies['is_writable']
            ->report(PathTestHelper::arbitraryFileAbsolute())
            ->shouldBeCalledOnce()
            ->willReturn($expected);
        $sut = $this->createSut();

        $actual = $sut->isWritable(PathTestHelper::arbitraryFilePath());

        self::assertSame($expected, $actual, 'Got correct writable status.');
    }

    public function providerIsWritable(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @covers ::mkdir
     *
     * @runInSeparateProcess
     */
    public function testMkdir(): void
    {
        BuiltinFunctionMocker::mock([
            'is_dir' => $this->prophesize(TestSpyInterface::class),
            'mkdir' => $this->prophesize(TestSpyInterface::class),
        ]);
        BuiltinFunctionMocker::$spies['is_dir']
            ->report(PathTestHelper::arbitraryDirAbsolute())
            ->shouldBeCalledOnce()
            ->willReturn(true);
        BuiltinFunctionMocker::$spies['mkdir']
            ->report(PathTestHelper::arbitraryDirAbsolute())
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $sut = $this->createSut();

        $sut->mkdir(PathTestHelper::arbitraryDirPath());
    }

    /**
     * @covers ::mkdir
     *
     * @runInSeparateProcess
     */
    public function testMkdirFailure(): void
    {
        $dirAbsolute = PathTestHelper::arbitraryDirAbsolute();
        BuiltinFunctionMocker::mock([
            'is_dir' => $this->prophesize(TestSpyInterface::class),
            'mkdir' => $this->prophesize(TestSpyInterface::class),
        ]);
        BuiltinFunctionMocker::$spies['is_dir']
            ->report($dirAbsolute)
            ->willReturn(false);
        BuiltinFunctionMocker::$spies['mkdir']
            ->report($dirAbsolute)
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $sut = $this->createSut();

        $message = sprintf('Failed to create directory at %s', $dirAbsolute);
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->mkdir(PathTestHelper::arbitraryDirPath());
        }, IOException::class, $message);
    }

    /**
     * @covers ::rm
     *
     * @dataProvider providerRm
     */
    public function testRm(string $path, ?OutputCallbackInterface $callback, int $timeout): void
    {
        $this->environment->setTimeLimit($timeout)
            ->shouldBeCalledOnce();
        $stagingDir = PathTestHelper::createPath($path);
        $this->symfonyFilesystem
            ->remove($stagingDir->absolute())
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->rm($stagingDir, $callback, $timeout);
    }

    public function providerRm(): array
    {
        return [
            'Default values' => [
                'path' => '/one/two',
                'callback' => null,
                'timeout' => ProcessInterface::DEFAULT_TIMEOUT,
            ],
            'Simple values' => [
                'path' => 'three/four',
                'callback' => new TestOutputCallback(),
                'timeout' => 10,
            ],
        ];
    }

    /** @covers ::rm */
    public function testRmException(): void
    {
        $message = 'Failed to remove directory.';
        $previous = new SymfonyIOException($message);
        $this->symfonyFilesystem
            ->remove(Argument::any())
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut): void {
            $sut->rm(PathTestHelper::stagingDirPath());
        }, IOException::class, $message, null, $previous::class);
    }

    /**
     * @covers ::touch
     *
     * @dataProvider providerTouch
     *
     * @runInSeparateProcess
     */
    public function testTouch(string $filename, array $givenArguments, array $expectedArguments): void
    {
        $path = PathTestHelper::createPath($filename);
        BuiltinFunctionMocker::mock(['touch' => $this->prophesize(TestSpyInterface::class)]);
        BuiltinFunctionMocker::$spies['touch']
            ->report($path->absolute(), ...$expectedArguments)
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $sut = $this->createSut();

        $sut->touch($path, ...$givenArguments);
    }

    public function providerTouch(): array
    {
        return [
            [
                'filename' => '/one.txt',
                'givenArguments' => [],
                'expectedArguments' => [null, null],
            ],
            [
                'filename' => '/one/two.txt',
                'givenArguments' => [null, null],
                'expectedArguments' => [null, null],
            ],
            [
                'filename' => '/one/two/three.txt',
                'givenArguments' => [946_702_800],
                'expectedArguments' => [946_702_800, null],
            ],
            [
                'filename' => '/one/two/three/four.txt',
                'givenArguments' => [978_325_200],
                'expectedArguments' => [978_325_200, null],
            ],
            [
                'filename' => '/one/two/three/four/five.txt',
                'givenArguments' => [null, 1_009_861_200],
                'expectedArguments' => [null, 1_009_861_200],
            ],
            [
                'filename' => '/one/two/three/four/five/six.txt',
                'givenArguments' => [1_041_397_200, 1_072_933_200],
                'expectedArguments' => [1_041_397_200, 1_072_933_200],
            ],
        ];
    }

    /**
     * @covers ::touch
     *
     * @runInSeparateProcess
     */
    public function testTouchFailure(): void
    {
        $path = PathTestHelper::arbitraryFilePath();
        BuiltinFunctionMocker::mock(['touch' => $this->prophesize(TestSpyInterface::class)]);
        BuiltinFunctionMocker::$spies['touch']
            ->report(Argument::cetera())
            ->willReturn(false);
        $sut = $this->createSut();

        $expectedExceptionMessage = sprintf('Failed to touch file at %s', $path->absolute());
        self::assertTranslatableException(static function () use ($sut, $path): void {
            $sut->touch($path);
        }, IOException::class, $expectedExceptionMessage);
    }
}
