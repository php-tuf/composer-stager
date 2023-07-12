<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\API\Process\Service;

use PhpTuf\ComposerStager\API\Process\Value\OutputTypeEnum;

/**
 * Receives streamed process output.
 *
 * This provides an interface for output callbacks accepted by API classes.
 *
 * @package Process
 *
 * @api This interface is subject to our backward compatibility promise and may be safely depended upon.
 */
interface OutputCallbackInterface
{
    /**
     * @param string $buffer
     *   A line of output.
     */
    public function __invoke(OutputTypeEnum $type, string $buffer): void;
}
