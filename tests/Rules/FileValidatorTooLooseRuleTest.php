<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanYiiPolicyRules\Rules\FileValidatorTooLooseRule;

/**
 * @extends RuleTestCase<FileValidatorTooLooseRule>
 */
final class FileValidatorTooLooseRuleTest extends RuleTestCase
{
    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/data/file-validator-too-loose.neon'];
    }

    protected function getRule(): Rule
    {
        return new FileValidatorTooLooseRule();
    }

    public function testReportsVariousFileValidatorFormatsWithoutTypeConstraints(): void
    {
        $this->analyse(
            [__DIR__ . '/data/file-validator-too-loose.php'],
            [
                [
                    'Yii file validator should declare at least one of \'extensions\' or \'mimeTypes\'; consider \'maxSize\' too.',
                    28,
                ],
                [
                    'Yii file validator should declare at least one of \'extensions\' or \'mimeTypes\'; consider \'maxSize\' too.',
                    29,
                ],
                [
                    'Yii file validator should declare at least one of \'extensions\' or \'mimeTypes\'; consider \'maxSize\' too.',
                    30,
                ],
            ],
        );
    }
}
