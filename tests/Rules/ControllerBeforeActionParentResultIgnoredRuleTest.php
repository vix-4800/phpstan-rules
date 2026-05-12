<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Tests\Rules;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanYiiPolicyRules\Rules\ControllerBeforeActionParentResultIgnoredRule;

/**
 * @extends RuleTestCase<ControllerBeforeActionParentResultIgnoredRule>
 */
final class ControllerBeforeActionParentResultIgnoredRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ControllerBeforeActionParentResultIgnoredRule(
            self::getContainer()->getByType(ReflectionProvider::class),
        );
    }

    public function testReportsIgnoredParentBeforeActionResult(): void
    {
        $this->analyse(
            [__DIR__ . '/data/controller-before-action-parent-result-ignored.php'],
            [
                [
                    'Controller beforeAction() must use parent::beforeAction() result.',
                    36,
                ],
                [
                    'Controller beforeAction() must use parent::beforeAction() result.',
                    56,
                ],
            ],
        );
    }
}
