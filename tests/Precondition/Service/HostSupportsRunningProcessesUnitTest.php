<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Process\Factory\ProcessFactoryInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\HostSupportsRunningProcesses;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableMessage;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Process\Process;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\HostSupportsRunningProcesses
 *
 * @covers ::__construct
 * @covers ::doAssertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::isFulfilled
 */
final class HostSupportsRunningProcessesUnitTest extends PreconditionTestCase
{
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
        $translatableFactory = new TestTranslatableFactory();

        return new HostSupportsRunningProcesses($environment, $processFactory, $translatableFactory);
    }

    public function testFulfilled(): void
    {
        $this->processFactory
            ->create(Argument::type('array'))
            ->shouldBeCalled();

        $this->doTestFulfilled('The host supports running independent PHP processes.');
    }

    /** @covers ::doAssertIsFulfilled */
    public function testUnfulfilled(): void
    {
        $message = __METHOD__;
        $previous = new LogicException(new TestTranslatableMessage($message));
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
