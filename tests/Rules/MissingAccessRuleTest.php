<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Tests\Rules;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanRules\Rules\MissingAccessRule;

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
                    'Controller action \'index\' is missing AccessControl behavior.',
                    34,
                ],
                [
                    'Controller action \'create-users-bot\' is missing AccessControl behavior.',
                    57,
                ],
                [
                    'Controller action \'create\' is missing AccessControl behavior.',
                    81,
                ],
            ],
        );
    }
}
