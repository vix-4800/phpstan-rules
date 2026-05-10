<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Tests\Rules;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanYiiPolicyRules\Rules\MutatingActionAllowsGetRule;

/**
 * @extends RuleTestCase<MutatingActionAllowsGetRule>
 */
final class MutatingActionAllowsGetRuleTest extends RuleTestCase
{
    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/data/mutating-action-allows-get.neon'];
    }

    protected function getRule(): Rule
    {
        return new MutatingActionAllowsGetRule(self::getContainer()->getByType(ReflectionProvider::class));
    }

    public function testReportsMutatingActionAllowsGet(): void
    {
        $this->analyse(
            [__DIR__ . '/data/mutating-action-allows-get.php'],
            [
                [
                    'Mutating controller action actionUpdate() must not allow GET and must be restricted to POST, PUT, PATCH, or DELETE.',
                    58,
                ],
                [
                    'Mutating controller action actionDelete() must not allow GET and must be restricted to POST, PUT, PATCH, or DELETE.',
                    63,
                ],
            ],
        );
    }
}
