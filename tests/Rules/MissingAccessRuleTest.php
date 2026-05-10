<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanYiiPolicyRules\Rules\MissingAccessRule;

/**
 * @extends RuleTestCase<MissingAccessRule>
 */
final class MissingAccessRuleTest extends RuleTestCase
{
    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/data/missing-access.neon'];
    }

    protected function getRule(): Rule
    {
        return new MissingAccessRule(self::getContainer()->getByType(ReflectionProvider::class));
    }

    public function testReportsOnlyActionsWithoutMatchingAccessRule(): void
    {
        $this->analyse(
            [__DIR__ . '/data/missing-access.php'],
            [
                [
                    'Controller action actionIndex() is missing AccessControl behavior.',
                    34,
                ],
                [
                    'Controller action actionCreateUsersBot() is missing AccessControl behavior.',
                    57,
                ],
                [
                    'Controller action actionCreate() is missing AccessControl behavior.',
                    81,
                ],
            ],
        );
    }
}
