<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Tests\Rules;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanYiiPolicyRules\Rules\LifecycleParentCallRule;

/**
 * @extends RuleTestCase<LifecycleParentCallRule>
 */
final class LifecycleParentCallRuleTest extends RuleTestCase
{
    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/data/lifecycle-parent-call.neon'];
    }

    protected function getRule(): Rule
    {
        return new LifecycleParentCallRule(self::getContainer()->getByType(ReflectionProvider::class));
    }

    public function testReportsMissingParentCallsInLifecycleMethods(): void
    {
        $this->analyse(
            [__DIR__ . '/data/lifecycle-parent-call.php'],
            [
                [
                    'ActiveRecord lifecycle method \'beforeValidate()\' must call parent::beforeValidate().',
                    64,
                ],
                [
                    'ActiveRecord lifecycle method \'beforeSave()\' must call parent::beforeSave().',
                    72,
                ],
                [
                    'ActiveRecord lifecycle method \'afterSave()\' must call parent::afterSave().',
                    80,
                ],
                [
                    'ActiveRecord lifecycle method \'afterFind()\' must call parent::afterFind().',
                    88,
                ],
                [
                    'ActiveRecord lifecycle method \'afterDelete()\' must call parent::afterDelete().',
                    95,
                ],
                [
                    'ActiveRecord lifecycle method \'beforeDelete()\' must call parent::beforeDelete().',
                    102,
                ],
                [
                    'ActiveRecord lifecycle method \'beforeValidate()\' must use parent::beforeValidate() result.',
                    110,
                ],
                [
                    'ActiveRecord lifecycle method \'beforeSave()\' must use parent::beforeSave() result.',
                    120,
                ],
                [
                    'ActiveRecord lifecycle method \'beforeDelete()\' must use parent::beforeDelete() result.',
                    130,
                ],
            ],
        );
    }
}
