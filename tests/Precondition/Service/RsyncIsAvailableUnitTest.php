<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface;
use PhpTuf\ComposerStager\API\Process\Factory\ProcessFactoryInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\RsyncIsAvailable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

#[CoversClass(RsyncIsAvailable::class)]
final class RsyncIsAvailableUnitTest extends PreconditionUnitTestCase
{
    protected const NAME = 'Rsync';
    protected const DESCRIPTION = 'Rsync must be available in order to sync files between the active and staging directories.';
    protected const FULFILLED_STATUS_MESSAGE = 'Rsync is available.';

    private const RSYNC_PATH = '/usr/bin/rsync';

    private ExecutableFinderInterface|ObjectProphecy $executableFinder;
    private ProcessFactoryInterface|ObjectProphecy $processFactory;
    private ProcessInterface|ObjectProphecy $process;

    protected function setUp(): void
    {
        $this->executableFinder = $this->prophesize(ExecutableFinderInterface::class);
        $this->executableFinder
            ->find('rsync')
            ->willReturn(self::RSYNC_PATH);
        $this->processFactory = $this->prophesize(ProcessFactoryInterface::class);
        $this->process = $this->prophesize(ProcessInterface::class);
        $this->process
            ->mustRun();
        $this->process
            ->getOutput()
            ->willReturn('');

        parent::setUp();
    }

    protected function createSut(): RsyncIsAvailable
    {
        $environment = $this->environment->reveal();
        $executableFinder = $this->executableFinder->reveal();
        $process = $this->process->reveal();
        $this->processFactory
            ->create(Argument::cetera())
            ->willReturn($process);
        $processFactory = $this->processFactory->reveal();
        $translatableFactory = self::createTranslatableFactory();

        return new RsyncIsAvailable($environment, $executableFinder, $processFactory, $translatableFactory);
    }

    #[DataProvider('providerFulfilled')]
    public function testFulfilled(string $output): void
    {
        $this->executableFinder
            ->find('rsync')
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->process
            ->mustRun()
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->process
            ->getOutput()
            ->willReturn($output);
        $this->processFactory
            ->create([
                self::RSYNC_PATH,
                '--version',
            ])
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE)
            ->willReturn($this->process->reveal());

        $this->doTestFulfilled(self::FULFILLED_STATUS_MESSAGE);
    }

    public static function providerFulfilled(): array
    {
        return [
            'Command name on first line' => [
                "rsync  version 2..9  protocol version 29
Copyright (C) 1996-2006.
<http://rsync.samba.org/>",
            ],
            'Command name on second line' => [
                "openrsync: protocol version 29
rsync version 2.6.9 compatible",
            ],
            'Funny spacing' => ['  rsync  version  42  '],
        ];
    }

    public function testExecutableNotFound(): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();

        $previous = LogicException::class;
        $this->executableFinder
            ->find('rsync')
            ->willThrow($previous);
        $sut = $this->createSut();

        $message = 'Cannot find rsync.';
        self::assertTranslatableMessage(
            $message,
            $sut->getStatusMessage($activeDirPath, $stagingDirPath),
        );
        self::assertTranslatableException(static function () use ($sut, $activeDirPath, $stagingDirPath): void {
            $sut->assertIsFulfilled($activeDirPath, $stagingDirPath);
        }, PreconditionException::class, $message);
    }

    public function testFailedToCreateProcess(): void
    {
        $message = __METHOD__;
        $previous = new LogicException(self::createTranslatableExceptionMessage($message));
        $this->processFactory
            ->create(Argument::type('array'))
            ->willThrow($previous);

        $this->doTestUnfulfilled(sprintf(
            'Cannot check for rsync due to a host configuration problem: %s',
            $message,
        ), $previous::class);
    }

    public function testFailedToRunProcess(): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();

        $this->process
            ->mustRun()
            ->willThrow(LogicException::class);
        $sut = $this->createSut();

        $message = $this->invalidRsyncErrorMessage();
        self::assertTranslatableMessage(
            $message,
            $sut->getStatusMessage($activeDirPath, $stagingDirPath),
        );
        self::assertTranslatableException(static function () use ($sut, $activeDirPath, $stagingDirPath): void {
            $sut->assertIsFulfilled($activeDirPath, $stagingDirPath);
        }, PreconditionException::class, $message);
    }

    public function testFailedToGetOutput(): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();

        $previous = LogicException::class;
        $this->process
            ->getOutput()
            ->willThrow($previous);
        $sut = $this->createSut();

        $message = $this->invalidRsyncErrorMessage();
        self::assertTranslatableMessage(
            $message,
            $sut->getStatusMessage($activeDirPath, $stagingDirPath),
        );
        self::assertTranslatableException(static function () use ($sut, $activeDirPath, $stagingDirPath): void {
            $sut->assertIsFulfilled($activeDirPath, $stagingDirPath);
        }, PreconditionException::class, $message);
    }

    #[DataProvider('providerInvalidOutput')]
    public function testInvalidOutput(string $output): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();

        $this->process
            ->getOutput()
            ->willReturn($output);
        $sut = $this->createSut();

        $message = $this->invalidRsyncErrorMessage();
        self::assertTranslatableMessage(
            $message,
            $sut->getStatusMessage($activeDirPath, $stagingDirPath),
        );
        self::assertTranslatableException(static function () use ($sut, $activeDirPath, $stagingDirPath): void {
            $sut->assertIsFulfilled($activeDirPath, $stagingDirPath);
        }, PreconditionException::class, $message);
    }

    public static function providerInvalidOutput(): array
    {
        return [
            'No output' => [''],
            'Missing application name' => ['version 1.0.0'],
            'Missing version' => ['rsync'],
            'Incorrect application name' => ['invalid_application version 1.0.0'],
            'Extraneous characters' => ['x rsync version 1.0.0'],
        ];
    }

    private function invalidRsyncErrorMessage(): string
    {
        return sprintf('The rsync executable at %s is invalid.', self::RSYNC_PATH);
    }
}
