<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Process\Factory\ProcessFactoryInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\HostSupportsRunningProcesses;
use PhpTuf\ComposerStager\Internal\SymfonyProcess\Value\Process;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\HostSupportsRunningProcesses
 *
 * @covers ::__construct
 * @covers ::doAssertIsFulfilled
 * @covers ::isFulfilled
 * @covers \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition::getStatusMessage
 */
final class HostSupportsRunningProcessesUnitTest extends PreconditionUnitTestCase
{
    protected const NAME = 'Host supports running processes';
    protected const DESCRIPTION = 'The host must support running independent PHP processes in order to run Composer and other shell commands.';
    protected const FULFILLED_STATUS_MESSAGE = 'The host supports running independent PHP processes.';

    private ProcessFactoryInterface|ObjectProphecy $processFactory;

    protected function setUp(): void
    {
        $this->processFactory = $this->prophesize(ProcessFactoryInterface::class);
        $this->processFactory
            ->create(Argument::any())
            ->willReturn(new Process([]));

        parent::setUp();
    }

    protected function createSut(): HostSupportsRunningProcesses
    {
        $environment = $this->environment->reveal();
        $processFactory = $this->processFactory->reveal();
        $translatableFactory = self::createTranslatableFactory();

        return new HostSupportsRunningProcesses($environment, $processFactory, $translatableFactory);
    }

    /**
     * @covers ::doAssertIsFulfilled
     * @covers ::getFulfilledStatusMessage
     */
    public function testFulfilled(): void
    {
        $this->processFactory
            ->create(Argument::type('array'))
            ->shouldBeCalled();

        $this->doTestFulfilled(self::FULFILLED_STATUS_MESSAGE);
    }

    /** @covers ::doAssertIsFulfilled */
    public function testUnfulfilled(): void
    {
        $message = __METHOD__;
        $previous = new LogicException(self::createTranslatableMessage($message));
        $this->processFactory
            ->create(Argument::type('array'))
            ->shouldBeCalled()
            ->willThrow($previous);

        $this->doTestUnfulfilled(sprintf(
            'The host does not support running independent PHP processes: %s',
            $message,
        ), $previous::class);
    }
}
