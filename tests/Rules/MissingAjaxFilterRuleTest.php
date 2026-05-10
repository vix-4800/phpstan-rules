<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Tests\Rules;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanYiiPolicyRules\Rules\MissingAjaxFilterRule;

/**
 * @extends RuleTestCase<MissingAjaxFilterRule>
 */
final class MissingAjaxFilterRuleTest extends RuleTestCase
{
    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/data/missing-ajax-filter.neon'];
    }

    protected function getRule(): Rule
    {
        return new MissingAjaxFilterRule(self::getContainer()->getByType(ReflectionProvider::class));
    }

    public function testReportsOnlyAjaxActionsWithoutMatchingAjaxFilter(): void
    {
        $this->analyse(
            [__DIR__ . '/data/missing-ajax-filter.php'],
            [
                [
                    'AJAX controller action actionSearch() is missing AjaxFilter behavior.',
                    37,
                ],
                [
                    'AJAX controller action actionStatus() is missing AjaxFilter behavior.',
                    59,
                ],
            ],
        );
    }
}
