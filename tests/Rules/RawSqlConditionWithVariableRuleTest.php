<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanRules\Rules\RawSqlConditionWithVariableRule;

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
                    'Do not build raw SQL strings with variables; use hash/operator format or bound params.',
                    96,
                ],
                [
                    'Do not build raw SQL strings with variables; use hash/operator format or bound params.',
                    97,
                ],
                [
                    'Do not build raw SQL strings with variables; use hash/operator format or bound params.',
                    98,
                ],
                [
                    'Do not build raw SQL strings with variables; use hash/operator format or bound params.',
                    99,
                ],
                [
                    'Do not build raw SQL strings with variables; use hash/operator format or bound params.',
                    100,
                ],
                [
                    'Do not build raw SQL strings with variables; use hash/operator format or bound params.',
                    101,
                ],
                [
                    'Do not build raw SQL strings with variables; use hash/operator format or bound params.',
                    102,
                ],
                [
                    'Do not build raw SQL strings with variables; use hash/operator format or bound params.',
                    103,
                ],
                [
                    'Do not build raw SQL strings with variables; use hash/operator format or bound params.',
                    113,
                ],
                [
                    'Do not build raw SQL strings with variables; use hash/operator format or bound params.',
                    114,
                ],
            ],
        );
    }
}
