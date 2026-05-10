<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanYiiPolicyRules\Rules\UnboundedQueryResultRule;

/**
 * @extends RuleTestCase<UnboundedQueryResultRule>
 */
final class UnboundedQueryResultRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new UnboundedQueryResultRule();
    }

    public function testReportsUnboundedQueryResults(): void
    {
        $this->analyse(
            [__DIR__ . '/data/unbounded-query-result.php'],
            [
                [
                    'Do not execute unbounded query result with all() or column(); use limit(), page(), batch(), each(), exists(), count(), or a DataProvider when appropriate.',
                    112,
                ],
                [
                    'Do not execute unbounded query result with all() or column(); use limit(), page(), batch(), each(), exists(), count(), or a DataProvider when appropriate.',
                    117,
                ],
                [
                    'Do not execute unbounded query result with all() or column(); use limit(), page(), batch(), each(), exists(), count(), or a DataProvider when appropriate.',
                    122,
                ],
            ],
        );
    }
}
