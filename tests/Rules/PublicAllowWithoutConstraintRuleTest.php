<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Tests\Rules;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanRules\Rules\PublicAllowWithoutConstraintRule;

/**
 * @extends RuleTestCase<PublicAllowWithoutConstraintRule>
 */
final class PublicAllowWithoutConstraintRuleTest extends RuleTestCase
{
    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/data/public-allow-without-constraint.neon'];
    }

    protected function getRule(): Rule
    {
        return new PublicAllowWithoutConstraintRule(self::getContainer()->getByType(ReflectionProvider::class));
    }

    public function testReportsPublicAllowWithoutConstraint(): void
    {
        $this->analyse(
            [__DIR__ . '/data/public-allow-without-constraint.php'],
            [
                [
                    'AccessControl rule allows public access without roles, permissions, matchCallback, ips, verbs, or actions.',
                    36,
                ],
            ],
        );
    }
}
