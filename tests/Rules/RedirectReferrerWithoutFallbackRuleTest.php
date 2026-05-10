<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanYiiPolicyRules\Rules\RedirectReferrerWithoutFallbackRule;

/**
 * @extends RuleTestCase<RedirectReferrerWithoutFallbackRule>
 */
final class RedirectReferrerWithoutFallbackRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new RedirectReferrerWithoutFallbackRule();
    }

    public function testReportsRedirectReferrerWithoutFallback(): void
    {
        $this->analyse(
            [__DIR__ . '/data/redirect-referrer-without-fallback.php'],
            [
                [
                    'Do not redirect to request referrer without fallback route and allowlist validation.',
                    14,
                ],
                [
                    'Do not redirect to request referrer without fallback route and allowlist validation.',
                    19,
                ],
            ],
        );
    }
}
