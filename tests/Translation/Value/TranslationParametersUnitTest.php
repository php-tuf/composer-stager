<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Value;

use AssertionError;
use PhpTuf\ComposerStager\Internal\Translation\Value\TranslationParameters;
use PhpTuf\ComposerStager\Tests\TestCase;
use stdClass;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Translation\Value\TranslationParameters */
final class TranslationParametersUnitTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getAll
     * @covers ::setValidParameters
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality(array $parameters): void
    {
        $sut = new TranslationParameters($parameters);

        self::assertSame($parameters, $sut->getAll(), 'Returned correct parameters.');
    }

    public function providerBasicFunctionality(): array
    {
        return [
            'No parameters' => [
                'parameters' => [],
            ],
            'Single string value' => [
                'parameters' => ['%placeholder' => 'value'],
            ],
            'Multiple string values' => [
                'parameters' => [
                    '%mood' => 'happy',
                    '%size' => 'little',
                ],
            ],
            'Minimal valid placeholder' => [
                'parameters' => ['%x' => ''],
            ],
            'Alphanumeric placeholder' => [
                'parameters' => ['%ab0' => ''],
            ],
            'Long placeholder' => [
                'parameters' => [str_pad('%', 512, 'a_b_c_d_e_f_g_h_i_j_k_l_m_n_o_p_q_r_s_t_u_v_w_x_y_z_0123456789') => ''],
            ],
            'Long value' => [
                'parameters' => ['%placeholder' => str_pad('%', 512, 'a_b_c_d_e_f_g_h_i_j_k_l_m_n_o_p_q_r_s_t_u_v_w_x_y_z_0123456789')],
            ],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::getAll
     * @covers ::setValidParameters
     */
    public function testDefaultParameters(): void
    {
        $sut = new TranslationParameters();

        self::assertEquals([], $sut->getAll(), 'Got correct default parameters.');
    }

    /**
     * @covers ::__construct
     * @covers ::getAll
     * @covers ::setValidParameters
     *
     * @dataProvider providerInvalidPlaceholders
     */
    public function testInvalidPlaceholders(array $given, array $expected, mixed $invalidPlaceholder): void
    {
        // Disable assertions so production error-handling can be tested.
        ini_set('zend.assertions', 0);
        $sut = new TranslationParameters($given);

        self::assertSame($expected, $sut->getAll(), 'Returned sanitized array on failure.');

        // Re-enable assertions so development error-handling can be tested.
        ini_set('zend.assertions', 1);

        self::assertTranslatableException(static function () use ($given): void {
            new TranslationParameters($given);
        }, AssertionError::class, sprintf(
            'Placeholders must be in the form /^%%\w+$/, i.e., a leading percent sign (%%) followed '
            . 'by one or more alphanumeric characters and underscores, e.g., "%%example". Got %s.',
            var_export($invalidPlaceholder, true),
        ));
    }

    public function providerInvalidPlaceholders(): array
    {
        return [
            'Empty string' => [
                'given' => ['' => ''],
                'expected' => [],
                'invalidPlaceholder' => '',
            ],
            'Non-string' => [
                'given' => [42 => ''],
                'expected' => [],
                'invalidPlaceholder' => 42,
            ],
            'Leading delimiter only' => [
                'given' => ['%' => ''],
                'expected' => [],
                'invalidPlaceholder' => '%',
            ],
            'No delimiter' => [
                'given' => ['bad' => ''],
                'expected' => [],
                'invalidPlaceholder' => 'bad',
            ],
            'In the middle' => [
                'given' => ['left%right' => ''],
                'expected' => [],
                'invalidPlaceholder' => 'left%right',
            ],
            'Surrounded by whitespace' => [
                'given' => [' %bad ' => ''],
                'expected' => [],
                'invalidPlaceholder' => ' %bad ',
            ],
            'In the middle with whitespace' => [
                'given' => ['left % right' => ''],
                'expected' => [],
                'invalidPlaceholder' => 'left % right',
            ],
            'Mixed with valid values' => [
                'given' => [
                    '%one' => '',
                    '%b@d' => '',
                    '%two' => '',
                ],
                'expected' => [
                    '%one' => '',
                    '%two' => '',
                ],
                'invalidPlaceholder' => '%b@d',
            ],
            'Special characters' => [
                'given' => ['%b@d' => ''],
                'expected' => [],
                'invalidPlaceholder' => '%b@d',
            ],
            // Make sure special characters don't cause PREG errors.
            'Special regular expression characters' => [
                'given' => ['/%.*/' => ''],
                'expected' => [],
                'invalidPlaceholder' => '/%.*/',
            ],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::getAll
     * @covers ::setValidParameters
     *
     * @dataProvider providerInvalidValues
     */
    public function testInvalidValues(array $given, array $expected, string $invalidType): void
    {
        // Disable assertions so production error-handling can be tested.
        ini_set('zend.assertions', 0);
        $sut = new TranslationParameters($given);

        self::assertSame($expected, $sut->getAll(), 'Returned sanitized array on failure.');

        // Re-enable assertions so development error-handling can be tested.
        ini_set('zend.assertions', 1);

        self::assertTranslatableException(static function () use ($given): void {
            new TranslationParameters($given);
        }, AssertionError::class, sprintf(
            'Placeholder values must be strings. Got %s.',
            $invalidType,
        ));
    }

    public function providerInvalidValues(): array
    {
        return [
            'Null' => [
                'given' => [null],
                'expected' => [],
                'invalidType' => 'null',
            ],
            'Array' => [
                'given' => [[]],
                'expected' => [],
                'invalidType' => 'array',
            ],
            'Object' => [
                'given' => ['%class' => new stdClass()],
                'expected' => [],
                'invalidType' => 'stdClass',
            ],
            'Mixed with valid values' => [
                'given' => [
                    '%string1' => 'string1',
                    '%invalid' => null,
                    '%string2' => 'string2',
                ],
                'expected' => [
                    '%string1' => 'string1',
                    '%string2' => 'string2',
                ],
                'invalidType' => 'null',
            ],
        ];
    }
}
