<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanYiiPolicyRules\Rules\ScenarioAssignedAfterLoadRule;

/**
 * @extends RuleTestCase<ScenarioAssignedAfterLoadRule>
 */
final class ScenarioAssignedAfterLoadRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ScenarioAssignedAfterLoadRule();
    }

    public function testReportsScenarioAssignedAfterMassAssignment(): void
    {
        $this->analyse(
            [__DIR__ . '/data/scenario-assigned-after-load.php'],
            [
                [
                    'Assign model scenario before load(), setAttributes(), or attributes mass assignment.',
                    41,
                ],
                [
                    'Assign model scenario before load(), setAttributes(), or attributes mass assignment.',
                    48,
                ],
                [
                    'Assign model scenario before load(), setAttributes(), or attributes mass assignment.',
                    55,
                ],
                [
                    'Assign model scenario before load(), setAttributes(), or attributes mass assignment.',
                    62,
                ],
            ],
        );
    }
}
