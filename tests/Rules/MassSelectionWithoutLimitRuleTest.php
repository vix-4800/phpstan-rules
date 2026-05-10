<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanYiiPolicyRules\Rules\MassSelectionWithoutLimitRule;

/**
 * @extends RuleTestCase<MassSelectionWithoutLimitRule>
 */
final class MassSelectionWithoutLimitRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new MassSelectionWithoutLimitRule();
    }

    public function testReportsFindAllWithoutLimit(): void
    {
        $this->analyse(
            [__DIR__ . '/data/mass-selection-without-limit.php'],
            [
                [
                    'Do not call find()->all() without limit().',
                    39,
                ],
                [
                    'Do not call find()->all() without limit().',
                    56,
                ],
            ],
        );
    }
}
