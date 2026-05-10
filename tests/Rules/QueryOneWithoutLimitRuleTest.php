<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanYiiPolicyRules\Rules\QueryOneWithoutLimitRule;

/**
 * @extends RuleTestCase<QueryOneWithoutLimitRule>
 */
final class QueryOneWithoutLimitRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new QueryOneWithoutLimitRule();
    }

    public function testReportsOneWithoutLimit(): void
    {
        $this->analyse(
            [__DIR__ . '/data/query-one-without-limit.php'],
            [
                [
                    'Call limit(1) before one().',
                    54,
                ],
                [
                    'Call limit(1) before one().',
                    59,
                ],
            ],
        );
    }
}
