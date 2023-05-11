<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\LogicException;
use PhpTuf\ComposerStager\Infrastructure\Factory\Process\ProcessFactoryInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinderInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ComposerIsAvailable;
use Prophecy\Argument;
use Symfony\Component\Process\Exception\LogicException as SymfonyLogicException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ComposerIsAvailable
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::getUnfulfilledStatusMessage
 * @covers ::isFulfilled
 * @covers ::isValidExecutable
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\Factory\Process\ProcessFactoryInterface|\Prophecy\Prophecy\ObjectProphecy $processFactory
 * @property \PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinderInterface|\Prophecy\Prophecy\ObjectProphecy $executableFinder
 * @property \Symfony\Component\Process\Process|\Prophecy\Prophecy\ObjectProphecy $process
 */
final class ComposerIsAvailableUnitTest extends PreconditionTestCase
{
    private const COMPOSER_PATH = '/usr/bin/composer';

    protected function setUp(): void
    {
        $this->executableFinder = $this->prophesize(ExecutableFinderInterface::class);
        $this->executableFinder
            ->find('composer')
            ->willReturn(self::COMPOSER_PATH);
        $this->processFactory = $this->prophesize(ProcessFactoryInterface::class);
        $this->process = $this->prophesize(Process::class);
        $this->process
            ->mustRun()
            ->willReturn($this->process);
        $this->process
            ->getOutput()
            ->willReturn(json_encode([
                'application' => [
                    'name' => 'Composer',
                    'version' => '2.0.0',
                ],
            ], JSON_THROW_ON_ERROR));

        parent::setUp();
    }

    protected function createSut(): ComposerIsAvailable
    {
        $executableFinder = $this->executableFinder->reveal();
        $process = $this->process->reveal();
        $this->processFactory
            ->create(Argument::cetera())
            ->willReturn($process);
        $processFactory = $this->processFactory->reveal();

        return new ComposerIsAvailable($executableFinder, $processFactory);
    }

    /** @covers ::isValidExecutable */
    public function testFulfilled(): void
    {
        $this->executableFinder
            ->find('composer')
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->process
            ->mustRun()
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->processFactory
            ->create([
                self::COMPOSER_PATH,
                'list',
                '--format=json',
            ])
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE)
            ->willReturn($this->process->reveal());

        $this->doTestFulfilled('Composer is available.');
    }

    public function testExecutableNotFound(): void
    {
        $this->executableFinder
            ->find('composer')
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE)
            ->willThrow(LogicException::class);

        $this->doTestUnfulfilled('Composer cannot be found.');
    }

    public function testFailedToCreateProcess(): void
    {
        $this->processFactory
            ->create(Argument::type('array'))
            ->willThrow(new LogicException('Configuration problem.'));

        $this->doTestUnfulfilled('Cannot check for Composer due to a host configuration problem: Configuration problem.');
    }

    public function testFailedToRunProcess(): void
    {
        $this->process
            ->mustRun()
            ->willThrow(ProcessFailedException::class);

        $this->doTestUnfulfilled($this->invalidComposerErrorMessage());
    }

    public function testFailedToGetOutput(): void
    {
        $this->process
            ->getOutput()
            ->willThrow(SymfonyLogicException::class);

        $this->doTestUnfulfilled($this->invalidComposerErrorMessage());
    }

    /** @dataProvider providerInvalidOutput */
    public function testInvalidOutput(string $output): void
    {
        $this->process
            ->getOutput()
            ->willReturn($output);

        $this->doTestUnfulfilled($this->invalidComposerErrorMessage());
    }

    public function providerInvalidOutput(): array
    {
        return [
            'No output' => [''],
            'Empty JSON object' => ['{}'],
            'Missing application name' => [
                json_encode((object) [
                    'application' => [],
                ], JSON_THROW_ON_ERROR),
            ],
            'Incorrect application name' => [
                json_encode((object) [
                    'application' => ['name' => 'Incorrect'],
                ], JSON_THROW_ON_ERROR),
            ],
        ];
    }

    private function invalidComposerErrorMessage(): string
    {
        return sprintf('The Composer executable at %s is invalid.', self::COMPOSER_PATH);
    }
}
