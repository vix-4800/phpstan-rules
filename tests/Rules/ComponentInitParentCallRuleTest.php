<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Tests\Rules;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanYiiPolicyRules\Rules\ComponentInitParentCallRule;

/**
 * @extends RuleTestCase<ComponentInitParentCallRule>
 */
final class ComponentInitParentCallRuleTest extends RuleTestCase
{
    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/data/component-init-parent-call.neon'];
    }

    protected function getRule(): Rule
    {
        return new ComponentInitParentCallRule(self::getContainer()->getByType(ReflectionProvider::class));
    }

    public function testReportsMissingParentInitCalls(): void
    {
        $this->analyse(
            [__DIR__ . '/data/component-init-parent-call.php'],
            [
                [
                    'Yii init() override must call parent::init().',
                    43,
                ],
                [
                    'Yii init() override must call parent::init().',
                    50,
                ],
                [
                    'Yii init() override must call parent::init().',
                    57,
                ],
                [
                    'Yii init() override must call parent::init().',
                    64,
                ],
            ],
        );
    }
}
