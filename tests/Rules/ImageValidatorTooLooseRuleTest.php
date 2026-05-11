<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanYiiPolicyRules\Rules\ImageValidatorTooLooseRule;

/**
 * @extends RuleTestCase<ImageValidatorTooLooseRule>
 */
final class ImageValidatorTooLooseRuleTest extends RuleTestCase
{
    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/data/image-validator-too-loose.neon'];
    }

    protected function getRule(): Rule
    {
        return new ImageValidatorTooLooseRule();
    }

    public function testReportsLooseImageValidatorsWithoutBounds(): void
    {
        $this->analyse(
            [__DIR__ . '/data/image-validator-too-loose.php'],
            [
                [
                    'Yii image validator rule should declare extensions, mimeTypes, maxSize, minWidth, or maxWidth.',
                    25,
                ],
                [
                    'Yii image validator rule should declare extensions, mimeTypes, maxSize, minWidth, or maxWidth.',
                    26,
                ],
                [
                    'Yii image validator rule should declare extensions, mimeTypes, maxSize, minWidth, or maxWidth.',
                    27,
                ],
            ],
        );
    }
}
