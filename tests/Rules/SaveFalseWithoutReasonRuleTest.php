<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanYiiPolicyRules\Rules\SaveFalseWithoutReasonRule;

/**
 * @extends RuleTestCase<SaveFalseWithoutReasonRule>
 */
final class SaveFalseWithoutReasonRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new SaveFalseWithoutReasonRule(['console\\migrations']);
    }

    public function testReportsSaveFalseOutsideAllowedNamespaces(): void
    {
        $this->analyse(
            [__DIR__ . '/data/save-false-without-reason.php'],
            [
                [
                    'Do not call save(false) without explicit validation bypass reason.',
                    16,
                ],
                [
                    'Avoid save(false, explicit attributes); validation is bypassed for selected fields.',
                    17,
                ],
            ],
        );
    }
}
