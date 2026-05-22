<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Tests\Rules;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanRules\Rules\ResponseFormatAssignmentInControllerRule;

/**
 * @extends RuleTestCase<ResponseFormatAssignmentInControllerRule>
 */
final class ResponseFormatAssignmentInControllerRuleTest extends RuleTestCase
{
    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/data/response-format-assignment-in-controller.neon'];
    }

    protected function getRule(): Rule
    {
        return new ResponseFormatAssignmentInControllerRule(self::getContainer()->getByType(ReflectionProvider::class));
    }

    public function testReportsJsonAndXmlResponseFormatAssignmentsInControllers(): void
    {
        $this->analyse(
            [__DIR__ . '/data/response-format-assignment-in-controller.php'],
            [
                [
                    'Do not assign JSON/XML response formats in Yii controllers; return $this->asJson() or $this->asXml() instead.',
                    49,
                ],
                [
                    'Do not assign JSON/XML response formats in Yii controllers; return $this->asJson() or $this->asXml() instead.',
                    54,
                ],
            ],
        );
    }
}
