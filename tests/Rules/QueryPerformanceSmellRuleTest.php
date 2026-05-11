<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanYiiPolicyRules\Rules\QueryPerformanceSmellRule;

/**
 * @extends RuleTestCase<QueryPerformanceSmellRule>
 */
final class QueryPerformanceSmellRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new QueryPerformanceSmellRule();
    }

    public function testReportsQueryPerformanceSmells(): void
    {
        $this->analyse(
            [__DIR__ . '/data/query-performance-smell.php'],
            [
                [
                    'Use query count() instead of count(query->all()) or count(query->column()) to avoid loading rows into memory.',
                    149,
                ],
                [
                    'Use query count() instead of count(query->all()) or count(query->column()) to avoid loading rows into memory.',
                    154,
                ],
                [
                    'Use query exists() instead of loading one() and comparing it with null.',
                    159,
                ],
                [
                    'Use query exists() instead of loading one() and comparing it with null.',
                    164,
                ],
                [
                    'Use query exists() instead of loading one() and comparing it with null.',
                    169,
                ],
                [
                    'Use query exists() instead of loading one() and comparing it with null.',
                    174,
                ],
                [
                    'Use query exists() instead of comparing count() with zero/one when only existence is needed.',
                    179,
                ],
                [
                    'Use query exists() instead of comparing count() with zero/one when only existence is needed.',
                    184,
                ],
                [
                    'Use query exists() instead of comparing count() with zero/one when only existence is needed.',
                    189,
                ],
                [
                    'Use query exists() instead of comparing count() with zero/one when only existence is needed.',
                    194,
                ],
                [
                    'Use query exists() instead of comparing count() with zero/one when only existence is needed.',
                    199,
                ],
                [
                    'Use query exists() instead of comparing count() with zero/one when only existence is needed.',
                    204,
                ],
                [
                    'Use query exists() instead of comparing count() with zero/one when only existence is needed.',
                    209,
                ],
                [
                    'Use query exists() instead of comparing count() with zero/one when only existence is needed.',
                    214,
                ],
                [
                    'Use query exists() instead of comparing count() with zero/one when only existence is needed.',
                    219,
                ],
                [
                    'Use query exists() instead of comparing count() with zero/one when only existence is needed.',
                    224,
                ],
                [
                    'Use query exists() instead of comparing count() with zero/one when only existence is needed.',
                    229,
                ],
                [
                    'Use query exists() instead of comparing count() with zero/one when only existence is needed.',
                    234,
                ],
                [
                    'Use query exists() instead of comparing count() with zero/one when only existence is needed.',
                    239,
                ],
                [
                    'Use query exists() instead of comparing count() with zero/one when only existence is needed.',
                    244,
                ],
                [
                    'Use query exists() instead of comparing count() with zero/one when only existence is needed.',
                    249,
                ],
                [
                    'Use query exists() instead of comparing count() with zero/one when only existence is needed.',
                    254,
                ],
                [
                    'Use query count() instead of count(query->all()) or count(query->column()) to avoid loading rows into memory.',
                    259,
                ],
                [
                    'Use query count() instead of count(query->all()) or count(query->column()) to avoid loading rows into memory.',
                    264,
                ],
                [
                    'Use Yii::$app->user->identity instead of findOne() with the current user id.',
                    269,
                ],
                [
                    'Use Yii::$app->user->identity instead of findOne() with the current user id.',
                    274,
                ],
                [
                    'Use Yii::$app->user->identity instead of findOne() with the current user id.',
                    279,
                ],
                [
                    'Use Yii::$app->user->identity instead of findOne() with the current user id.',
                    284,
                ],
                [
                    'Use Yii::$app->user->identity instead of findOne() with the current user id.',
                    289,
                ],
            ],
        );
    }
}
