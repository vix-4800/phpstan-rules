<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanYiiPolicyRules\Rules\DeleteAllOrUpdateAllWithoutWhereRule;

/**
 * @extends RuleTestCase<DeleteAllOrUpdateAllWithoutWhereRule>
 */
final class DeleteAllOrUpdateAllWithoutWhereRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new DeleteAllOrUpdateAllWithoutWhereRule();
    }

    public function testReportsDeleteAllOrUpdateAllWithoutWhere(): void
    {
        $this->analyse(
            [__DIR__ . '/data/delete-all-or-update-all-without-where.php'],
            [
                [
                    'Do not call deleteAll() without a non-empty condition.',
                    26,
                ],
                [
                    'Do not call deleteAll() without a non-empty condition.',
                    27,
                ],
                [
                    'Do not call deleteAll() without a non-empty condition.',
                    28,
                ],
                [
                    'Do not call deleteAll() without a non-empty condition.',
                    29,
                ],
                [
                    'Do not call updateAll() without a non-empty condition.',
                    34,
                ],
                [
                    'Do not call updateAll() without a non-empty condition.',
                    35,
                ],
                [
                    'Do not call updateAll() without a non-empty condition.',
                    36,
                ],
                [
                    'Do not call updateAll() without a non-empty condition.',
                    37,
                ],
                [
                    'Do not call updateAll() without a non-empty condition.',
                    38,
                ],
            ],
        );
    }
}
