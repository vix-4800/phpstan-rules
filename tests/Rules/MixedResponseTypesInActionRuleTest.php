<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Tests\Rules;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanRules\Rules\MixedResponseTypesInActionRule;

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
                    'Controller action \'index\' mixes JSON and non-JSON responses; keep one response type per action.',
                    41,
                ],
                [
                    'Controller action \'preview\' mixes JSON and non-JSON responses; keep one response type per action.',
                    64,
                ],
                [
                    'Controller action \'delete\' mixes JSON and non-JSON responses; keep one response type per action.',
                    73,
                ],
            ],
        );
    }
}
