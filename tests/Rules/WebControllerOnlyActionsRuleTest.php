<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Tests\Rules;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanRules\Rules\WebControllerOnlyActionsRule;

/**
 * @extends RuleTestCase<WebControllerOnlyActionsRule>
 */
final class WebControllerOnlyActionsRuleTest extends RuleTestCase
{
    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/data/web-controller-only-actions.neon'];
    }

    protected function getRule(): Rule
    {
        return new WebControllerOnlyActionsRule(self::getContainer()->getByType(ReflectionProvider::class));
    }

    public function testReportsNonActionMethodsInWebControllers(): void
    {
        $this->analyse(
            [__DIR__ . '/data/web-controller-only-actions.php'],
            [
                [
                    'Web controller method \'helper()\' is not allowed; keep only public action*() methods and standard Yii overrides.',
                    44,
                ],
                [
                    'Web controller method \'loadModel()\' is not allowed; keep only public action*() methods and standard Yii overrides.',
                    48,
                ],
                [
                    'Web controller method \'actionInternal()\' is not allowed; keep only public action*() methods and standard Yii overrides.',
                    52,
                ],
                [
                    'Web controller method \'action()\' is not allowed; keep only public action*() methods and standard Yii overrides.',
                    56,
                ],
            ],
        );
    }
}
