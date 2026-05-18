<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Tests\Rules;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanRules\Rules\MissingVerbFilterRule;

/**
 * @extends RuleTestCase<MissingVerbFilterRule>
 */
final class MissingVerbFilterRuleTest extends RuleTestCase
{
    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/data/missing-verb-filter.neon'];
    }

    protected function getRule(): Rule
    {
        return new MissingVerbFilterRule(self::getContainer()->getByType(ReflectionProvider::class));
    }

    public function testReportsOnlyActionsWithoutMatchingVerbFilter(): void
    {
        $this->analyse(
            [__DIR__ . '/data/missing-verb-filter.php'],
            [
                [
                    'Controller action \'index\' is missing VerbFilter behavior.',
                    32,
                ],
                [
                    'Controller action \'create-users-bot\' is missing VerbFilter behavior.',
                    55,
                ],
                [
                    'Controller action \'create\' is missing VerbFilter behavior.',
                    80,
                ],
            ],
        );
    }
}
