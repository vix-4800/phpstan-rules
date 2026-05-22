<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Tests\Rules;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanRules\Rules\ResponseStatusCodeAssignmentInControllerRule;

/**
 * @extends RuleTestCase<ResponseStatusCodeAssignmentInControllerRule>
 */
final class ResponseStatusCodeAssignmentInControllerRuleTest extends RuleTestCase
{
    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/data/response-status-code-assignment-in-controller.neon'];
    }

    protected function getRule(): Rule
    {
        return new ResponseStatusCodeAssignmentInControllerRule(self::getContainer()->getByType(ReflectionProvider::class));
    }

    public function testReportsStatusCodeAssignmentsInController(): void
    {
        $this->analyse(
            [__DIR__ . '/data/response-status-code-assignment-in-controller.php'],
            [
                [
                    'Do not assign Yii::$app->response->statusCode inside Yii controllers; return a response and call setStatusCode() instead.',
                    59,
                ],
                [
                    'Do not assign Yii::$app->response->statusCode inside Yii controllers; return a response and call setStatusCode() instead.',
                    66,
                ],
            ],
        );
    }
}
