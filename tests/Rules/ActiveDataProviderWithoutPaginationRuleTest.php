<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Tests\Rules;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanYiiPolicyRules\Rules\ActiveDataProviderWithoutPaginationRule;

/**
 * @extends RuleTestCase<ActiveDataProviderWithoutPaginationRule>
 */
final class ActiveDataProviderWithoutPaginationRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ActiveDataProviderWithoutPaginationRule(
            self::getContainer()->getByType(ReflectionProvider::class),
        );
    }

    public function testReportsDataProviderWithoutPaginationInController(): void
    {
        $this->analyse(
            [__DIR__ . '/data/active-data-provider-without-pagination.php'],
            [
                [
                    'Do not disable pagination for ActiveDataProvider or SqlDataProvider in web context.',
                    42,
                ],
                [
                    'Do not disable pagination for ActiveDataProvider or SqlDataProvider in web context.',
                    50,
                ],
            ],
        );
    }
}
