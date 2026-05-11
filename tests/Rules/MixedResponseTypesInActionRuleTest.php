<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Tests\Rules;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanYiiPolicyRules\Rules\MixedResponseTypesInActionRule;

/**
 * @extends RuleTestCase<MixedResponseTypesInActionRule>
 */
final class MixedResponseTypesInActionRuleTest extends RuleTestCase
{
    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/data/mixed-response-types-in-action.neon'];
    }

    protected function getRule(): Rule
    {
        return new MixedResponseTypesInActionRule(self::getContainer()->getByType(ReflectionProvider::class));
    }

    public function testReportsMixedResponseTypesInAction(): void
    {
        $this->analyse(
            [__DIR__ . '/data/mixed-response-types-in-action.php'],
            [
                [
                    'Controller action \'index\' mixes JSON and HTML responses; keep one response type per action.',
                    36,
                ],
                [
                    'Controller action \'preview\' mixes JSON and HTML responses; keep one response type per action.',
                    59,
                ],
            ],
        );
    }
}
