<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Tests\Rules;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanYiiPolicyRules\Rules\CsrfDisabledWithoutCompensatingControlRule;

/**
 * @extends RuleTestCase<CsrfDisabledWithoutCompensatingControlRule>
 */
final class CsrfDisabledWithoutCompensatingControlRuleTest extends RuleTestCase
{
    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/data/csrf-disabled-without-compensating-control.neon'];
    }

    protected function getRule(): Rule
    {
        return new CsrfDisabledWithoutCompensatingControlRule(self::getContainer()->getByType(ReflectionProvider::class));
    }

    public function testReportsCsrfDisabledInsideControllerCode(): void
    {
        $this->analyse(
            [__DIR__ . '/data/csrf-disabled-without-compensating-control.php'],
            [
                [
                    'Disabling CSRF validation in action \'webhook\' requires a compensating control.',
                    31,
                ],
                [
                    'Disabling CSRF validation in beforeAction() requires a compensating control.',
                    36,
                ],
            ],
        );
    }
}
