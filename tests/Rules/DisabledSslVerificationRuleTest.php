<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanRules\Rules\DisabledSslVerificationRule;

/**
 * @extends RuleTestCase<DisabledSslVerificationRule>
 */
final class DisabledSslVerificationRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new DisabledSslVerificationRule();
    }

    public function testReportsDisabledSslVerification(): void
    {
        $this->analyse(
            [__DIR__ . '/data/disabled-ssl-verification.php'],
            [
                [
                    'SSL certificate verification must not be disabled.',
                    36,
                ],
                [
                    'SSL certificate verification must not be disabled.',
                    38,
                ],
                [
                    'SSL certificate verification must not be disabled.',
                    37,
                ],
                [
                    'SSL certificate verification must not be disabled.',
                    40,
                ],
                [
                    'SSL certificate verification must not be disabled.',
                    44,
                ],
                [
                    'SSL certificate verification must not be disabled.',
                    53,
                ],
                [
                    'SSL certificate verification must not be disabled.',
                    55,
                ],
                [
                    'SSL certificate verification must not be disabled.',
                    58,
                ],
            ],
        );
    }
}
