<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Tests\Rules;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanRules\Rules\MissingAjaxFilterRule;

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
                    'AJAX controller action \'search\' is missing AjaxFilter behavior.',
                    37,
                ],
                [
                    'AJAX controller action \'status\' is missing AjaxFilter behavior.',
                    59,
                ],
            ],
        );
    }
}
