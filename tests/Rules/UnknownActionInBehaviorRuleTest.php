<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Tests\Rules;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanYiiPolicyRules\Rules\UnknownActionInBehaviorRule;

/**
 * @extends RuleTestCase<UnknownActionInBehaviorRule>
 */
final class UnknownActionInBehaviorRuleTest extends RuleTestCase
{
    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/data/unknown-action-in-behavior.neon'];
    }

    protected function getRule(): Rule
    {
        return new UnknownActionInBehaviorRule(self::getContainer()->getByType(ReflectionProvider::class));
    }

    public function testReportsUnknownActionReferencesInsideBehaviors(): void
    {
        $this->analyse(
            [__DIR__ . '/data/unknown-action-in-behavior.php'],
            [
                [
                    'Behavior references unknown controller action id \'missing-only\' in only.',
                    54,
                ],
                [
                    'Behavior references unknown controller action id \'missing-except\' in except.',
                    55,
                ],
                [
                    'Behavior references unknown controller action id \'missing-rule\' in rules[*].actions.',
                    57,
                ],
                [
                    'Behavior references unknown controller action id \'missing-ajax\' in AjaxFilter::only.',
                    63,
                ],
                [
                    'Behavior references unknown controller action id \'missing-verb\' in VerbFilter::actions.',
                    68,
                ],
            ],
        );
    }
}
