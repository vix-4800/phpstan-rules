<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Tests\Rules;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanRules\Rules\SensitiveAttributeMarkedSafeRule;

/**
 * @extends RuleTestCase<SensitiveAttributeMarkedSafeRule>
 */
final class SensitiveAttributeMarkedSafeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new SensitiveAttributeMarkedSafeRule(
            self::getContainer()->getByType(ReflectionProvider::class),
            ['~^(id|user_id|created_by|status|auth_key)$~i'],
        );
    }

    public function testReportsSensitiveAttributesMarkedSafeWithoutScenario(): void
    {
        $this->analyse(
            [__DIR__ . '/data/sensitive-attribute-marked-safe.php'],
            [
                [
                    'Sensitive attribute \'status\' must not be mass assignable without scenario restriction.',
                    32,
                ],
                [
                    'Sensitive attribute \'created_by\' must not be mass assignable without scenario restriction.',
                    32,
                ],
                [
                    'Sensitive attribute \'user_id\' must not be mass assignable without scenario restriction.',
                    33,
                ],
                [
                    'Sensitive attribute \'auth_key\' must not be mass assignable without scenario restriction.',
                    34,
                ],
            ],
        );
    }
}
