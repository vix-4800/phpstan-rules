<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Tests\Rules;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanYiiPolicyRules\Rules\LifecycleSelfSaveRule;

/**
 * @extends RuleTestCase<LifecycleSelfSaveRule>
 */
final class LifecycleSelfSaveRuleTest extends RuleTestCase
{
    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/data/lifecycle-self-save.neon'];
    }

    protected function getRule(): Rule
    {
        return new LifecycleSelfSaveRule(self::getContainer()->getByType(ReflectionProvider::class));
    }

    public function testReportsSelfMutationsInsideLifecycleHooks(): void
    {
        $this->analyse(
            [__DIR__ . '/data/lifecycle-self-save.php'],
            [
                [
                    'ActiveRecord lifecycle method \'afterFind()\' must not call $this->save(), $this->update(), or $this->delete().',
                    79,
                ],
                [
                    'ActiveRecord lifecycle method \'beforeSave()\' must not call $this->save(), $this->update(), or $this->delete().',
                    87,
                ],
                [
                    'ActiveRecord lifecycle method \'afterDelete()\' must not call $this->save(), $this->update(), or $this->delete().',
                    97,
                ],
                [
                    'ActiveRecord lifecycle method \'afterValidate()\' must not call $this->save(), $this->update(), or $this->delete().',
                    106,
                ],
            ],
        );
    }
}
