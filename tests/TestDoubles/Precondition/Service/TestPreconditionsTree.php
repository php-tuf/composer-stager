<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestDoubles\Precondition\Service;

use PhpTuf\ComposerStager\API\Precondition\Service\PreconditionInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPreconditionsTree;
use PhpTuf\ComposerStager\Tests\TestDoubles\Environment\Service\TestEnvironment;
use PhpTuf\ComposerStager\Tests\TestUtils\TranslationTestHelper;

final class TestPreconditionsTree extends AbstractPreconditionsTree
{
    protected const NAME = 'Test preconditions tree';
    protected const DESCRIPTION = 'A generic preconditions tree for automated tests.';
    protected const FULFILLED_STATUS_MESSAGE = 'TestPreconditionsTree is unfulfilled.';

    protected function t(
        string $message,
        ?TranslationParametersInterface $parameters = null,
        ?string $domain = null,
    ): TranslatableInterface {
        return TranslationTestHelper::createTranslatableMessage($message);
    }

    public function __construct(PreconditionInterface ...$children)
    {
        $environment = new TestEnvironment();
        $translatableFactory = TranslationTestHelper::createTranslatableFactory();

        parent::__construct($environment, $translatableFactory, ...$children);
    }

    public function getName(): TranslatableInterface
    {
        return $this->t(self::NAME);
    }

    public function getDescription(): TranslatableInterface
    {
        return $this->t(self::DESCRIPTION);
    }

    protected function getFulfilledStatusMessage(): TranslatableInterface
    {
        return $this->t(self::FULFILLED_STATUS_MESSAGE);
    }
}
