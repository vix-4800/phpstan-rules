<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanYiiPolicyRules\Rules\RawSqlConditionWithVariableRule;

/**
 * @extends RuleTestCase<RawSqlConditionWithVariableRule>
 */
final class RawSqlConditionWithVariableRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new RawSqlConditionWithVariableRule();
    }

    public function testReportsRawSqlConditionWithVariable(): void
    {
        $this->analyse(
            [__DIR__ . '/data/raw-sql-condition-with-variable.php'],
            [
                [
                    'Do not build raw SQL condition strings with variables; use hash/operator format or bound params.',
                    74,
                ],
                [
                    'Do not build raw SQL condition strings with variables; use hash/operator format or bound params.',
                    75,
                ],
                [
                    'Do not build raw SQL condition strings with variables; use hash/operator format or bound params.',
                    76,
                ],
                [
                    'Do not build raw SQL condition strings with variables; use hash/operator format or bound params.',
                    77,
                ],
                [
                    'Do not build raw SQL condition strings with variables; use hash/operator format or bound params.',
                    78,
                ],
                [
                    'Do not build raw SQL condition strings with variables; use hash/operator format or bound params.',
                    79,
                ],
                [
                    'Do not build raw SQL condition strings with variables; use hash/operator format or bound params.',
                    80,
                ],
                [
                    'Do not build raw SQL condition strings with variables; use hash/operator format or bound params.',
                    81,
                ],
            ],
        );
    }
}
