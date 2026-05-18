<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanRules\Rules\RemoteFileGetContentsRule;

/**
 * @extends RuleTestCase<RemoteFileGetContentsRule>
 */
final class RemoteFileGetContentsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new RemoteFileGetContentsRule();
    }

    public function testReportsRemoteFileGetContents(): void
    {
        $this->analyse(
            [__DIR__ . '/data/remote-file-get-contents.php'],
            [
                [
                    'Remote file_get_contents() is forbidden.',
                    13,
                ],
                [
                    'Remote file_get_contents() is forbidden.',
                    14,
                ],
                [
                    'Remote file_get_contents() is forbidden.',
                    15,
                ],
                [
                    'Remote file_get_contents() is forbidden.',
                    17,
                ],
                [
                    'Remote file_get_contents() is forbidden.',
                    21,
                ],
            ],
        );
    }
}
