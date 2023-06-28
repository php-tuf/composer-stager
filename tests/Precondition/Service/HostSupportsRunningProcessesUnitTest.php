<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\Internal\Precondition\Service\HostSupportsRunningProcesses;
use PhpTuf\ComposerStager\Internal\Process\Factory\ProcessFactoryInterface;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Service\TestTranslator;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableExceptionMessage;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableMessage;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslationParameters;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Process\Process;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\HostSupportsRunningProcesses
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\API\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableAwareTrait
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Service\DomainOptions
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Value\TranslationParameters
 *
 * @property \PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface|\Prophecy\Prophecy\ObjectProphecy $translatableFactory
 */
final class HostSupportsRunningProcessesUnitTest extends PreconditionTestCase
{
    private ObjectProphecy|ProcessFactoryInterface $processFactory;

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
        /** @var \PhpTuf\ComposerStager\Internal\Process\Factory\ProcessFactory $processFactory */
        $processFactory = $this->processFactory->reveal();
        $translatableFactory = new TestTranslatableFactory();
        $translator = new TestTranslator();

        return new HostSupportsRunningProcesses($processFactory, $translatableFactory, $translator);
    }

    public function testFulfilled(): void
    {
        $this->processFactory
            ->create(Argument::type('array'))
            ->shouldBeCalled();

        $this->doTestFulfilled('The host supports running independent PHP processes.');
    }

    /** @covers ::assertIsFulfilled */
    public function testUnfulfilled(): void
    {
        $message = __METHOD__;
        $previous = new LogicException(new TestTranslatableMessage($message));
        $this->processFactory
            ->create(Argument::type('array'))
            ->shouldBeCalled()
            ->willThrow($previous);

        // @todo Now that ::doTestUnfulfilled() is taking a whole translatable message, it's no
        //   longer testing the translated value of the message, just its parts--which is more
        //   like testing assumptions than testing expectations. Add an argument to it to test
        //   the translated string, too.
        $this->doTestUnfulfilled(new TestTranslatableExceptionMessage(
            'The host does not support running independent PHP processes: %details',
            new TestTranslationParameters(['%details' => $message]),
        ), $previous::class);
    }
}
