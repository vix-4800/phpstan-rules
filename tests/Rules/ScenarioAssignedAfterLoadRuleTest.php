<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Tests\Rules;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanRules\Rules\ScenarioAssignedAfterLoadRule;

/**
 * @extends RuleTestCase<ScenarioAssignedAfterLoadRule>
 */
final class ScenarioAssignedAfterLoadRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ScenarioAssignedAfterLoadRule(
            self::getContainer()->getByType(ReflectionProvider::class),
        );
    }

    public function testReportsScenarioAssignedAfterMassAssignment(): void
    {
        $this->analyse(
            [__DIR__ . '/data/scenario-assigned-after-load.php'],
            [
                [
                    'Assign model scenario before load(), setAttributes(), or attributes mass assignment.',
                    47,
                ],
                [
                    'Assign model scenario before load(), setAttributes(), or attributes mass assignment.',
                    54,
                ],
                [
                    'Assign model scenario before load(), setAttributes(), or attributes mass assignment.',
                    61,
                ],
                [
                    'Assign model scenario before load(), setAttributes(), or attributes mass assignment.',
                    68,
                ],
                [
                    'Assign model scenario before load(), setAttributes(), or attributes mass assignment.',
                    84,
                ],
                [
                    'Assign model scenario before load(), setAttributes(), or attributes mass assignment.',
                    114,
                ],
            ],
        );
    }
}
