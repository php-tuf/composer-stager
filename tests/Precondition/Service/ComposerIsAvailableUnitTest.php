<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Exception\RuntimeException;
use PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface;
use PhpTuf\ComposerStager\API\Process\Service\ComposerProcessRunnerInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\ComposerIsAvailable;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\ComposerIsAvailable
 *
 * @covers ::__construct
 * @covers ::assertExecutableExists
 * @covers ::assertIsActuallyComposer
 * @covers ::isValidExecutable
 */
final class ComposerIsAvailableUnitTest extends PreconditionUnitTestCase
{
    protected const NAME = 'Composer';
    protected const DESCRIPTION = 'Composer must be available in order to stage commands.';
    protected const FULFILLED_STATUS_MESSAGE = 'Composer is available.';

    private const COMPOSER_PATH = '/usr/bin/composer';

    private ComposerProcessRunnerInterface|ObjectProphecy $composerProcessRunner;
    private ExecutableFinderInterface|ObjectProphecy $executableFinder;
    private OutputCallbackInterface|ObjectProphecy $outputCallback;

    protected function setUp(): void
    {
        $this->composerProcessRunner = $this->prophesize(ComposerProcessRunnerInterface::class);
        $this->executableFinder = $this->prophesize(ExecutableFinderInterface::class);
        $this->executableFinder
            ->find('composer')
            ->willReturn(self::COMPOSER_PATH);
        $this->outputCallback = $this->prophesize(OutputCallbackInterface::class);
        $this->outputCallback
            ->clearOutput();
        $this->outputCallback
            ->getOutput()
            ->willReturn([
                json_encode([
                    'application' => [
                        'name' => 'Composer',
                        'version' => '2.0.0',
                    ],
                ], JSON_THROW_ON_ERROR),
            ]);

        parent::setUp();
    }

    protected function createSut(): ComposerIsAvailable
    {
        return new ComposerIsAvailable(
            $this->composerProcessRunner->reveal(),
            $this->environment->reveal(),
            $this->executableFinder->reveal(),
            $this->outputCallback->reveal(),
            self::createTranslatableFactory(),
        );
    }

    /**
     * @covers ::assertExecutableExists
     * @covers ::assertIsActuallyComposer
     * @covers ::doAssertIsFulfilled
     * @covers ::getFulfilledStatusMessage
     * @covers ::isValidExecutable
     */
    public function testFulfilled(): void
    {
        $this->executableFinder
            ->find('composer')
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->composerProcessRunner
            ->run(['--format=json'], Argument::cetera())
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);

        $this->doTestFulfilled(self::FULFILLED_STATUS_MESSAGE);
    }

    /** @dataProvider providerComposerProcessRunnerException */
    public function testComposerProcessRunnerException(string $exception): void
    {
        $this->expectException(PreconditionException::class);
        $this->composerProcessRunner
            ->run(Argument::cetera())
            ->willThrow($exception);
        $sut = $this->createSut();

        $sut->assertIsFulfilled(self::activeDirPath(), self::stagingDirPath());
    }

    public function providerComposerProcessRunnerException(): array
    {
        return [
            [LogicException::class],
            [RuntimeException::class],
        ];
    }
}
