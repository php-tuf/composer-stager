<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Filesystem\Service;

use PhpTuf\ComposerStager\API\Environment\Service\EnvironmentInterface;
use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestDoubles\Process\Service\TestOutputCallback;
use PhpTuf\ComposerStager\Tests\TestDoubles\TestSpyInterface;
use PhpTuf\ComposerStager\Tests\TestUtils\BuiltinFunctionMocker;
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
        $translatableFactory = self::createTranslatableFactory();

        return new Filesystem($environment, $pathFactory, $symfonyFilesystem, $translatableFactory);
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
            ->report(self::arbitraryFileAbsolute())
            ->shouldBeCalledOnce()
            ->willReturn($expected);
        $sut = $this->createSut();

        $actual = $sut->isWritable(self::arbitraryFilePath());

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
            ->report(self::arbitraryDirAbsolute())
            ->shouldBeCalledOnce()
            ->willReturn(true);
        BuiltinFunctionMocker::$spies['mkdir']
            ->report(self::arbitraryDirAbsolute())
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $sut = $this->createSut();

        $sut->mkdir(self::arbitraryDirPath());
    }

    /**
     * @covers ::mkdir
     *
     * @runInSeparateProcess
     */
    public function testMkdirFailure(): void
    {
        $dirAbsolute = self::arbitraryDirAbsolute();
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
            $sut->mkdir(self::arbitraryDirPath());
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
        $stagingDir = self::createPath($path);
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
            $sut->rm(self::stagingDirPath());
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
        $path = self::createPath($filename);
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
        $path = self::arbitraryFilePath();
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
