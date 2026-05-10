<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Tests\Rules;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanYiiPolicyRules\Rules\NativeHeaderInControllerRule;

/**
 * @extends RuleTestCase<NativeHeaderInControllerRule>
 */
final class NativeHeaderInControllerRuleTest extends RuleTestCase
{
    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/data/native-header-in-controller.neon'];
    }

    protected function getRule(): Rule
    {
        return new NativeHeaderInControllerRule(self::getContainer()->getByType(ReflectionProvider::class));
    }

    public function testReportsNativeHeaderInController(): void
    {
        $this->analyse(
            [__DIR__ . '/data/native-header-in-controller.php'],
            [
                [
                    'Do not call native header() inside Yii controllers; use response component or asJson().',
                    22,
                ],
            ],
        );
    }
}
