<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanYiiPolicyRules\Rules\TransactionWithoutRollbackHandlingRule;

/**
 * @extends RuleTestCase<TransactionWithoutRollbackHandlingRule>
 */
final class TransactionWithoutRollbackHandlingRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new TransactionWithoutRollbackHandlingRule();
    }

    public function testReportsTransactionsWithoutRollbackHandling(): void
    {
        $this->analyse(
            [__DIR__ . '/data/transaction-without-rollback-handling.php'],
            [
                [
                    'Method starts a Yii transaction with beginTransaction() but does not call rollBack()/rollback() in a catch block.',
                    31,
                ],
                [
                    'Method starts a Yii transaction with beginTransaction() but does not call rollBack()/rollback() in a catch block.',
                    37,
                ],
            ],
        );
    }
}
