<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Tests\Rules;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanRules\Rules\MutatingActionAllowsGetRule;

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
                    'Mutating controller action \'update\' must not allow GET and must be restricted to POST, PUT, PATCH, or DELETE.',
                    90,
                ],
                [
                    'Mutating controller action \'delete\' must not allow GET and must be restricted to POST, PUT, PATCH, or DELETE.',
                    95,
                ],
                [
                    'Mutating controller action \'update-attributes\' must not allow GET and must be restricted to POST, PUT, PATCH, or DELETE.',
                    100,
                ],
                [
                    'Mutating controller action \'update-counters\' must not allow GET and must be restricted to POST, PUT, PATCH, or DELETE.',
                    105,
                ],
                [
                    'Mutating controller action \'update-all-counters\' must not allow GET and must be restricted to POST, PUT, PATCH, or DELETE.',
                    110,
                ],
                [
                    'Mutating controller action \'insert\' must not allow GET and must be restricted to POST, PUT, PATCH, or DELETE.',
                    115,
                ],
                [
                    'Mutating controller action \'rename-file\' must not allow GET and must be restricted to POST, PUT, PATCH, or DELETE.',
                    120,
                ],
            ],
        );
    }
}
