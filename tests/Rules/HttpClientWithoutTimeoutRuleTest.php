<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanRules\Rules\HttpClientWithoutTimeoutRule;

/**
 * @extends RuleTestCase<HttpClientWithoutTimeoutRule>
 */
final class HttpClientWithoutTimeoutRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new HttpClientWithoutTimeoutRule();
    }

    public function testReportsHttpClientCallsWithoutTimeout(): void
    {
        $this->analyse(
            [__DIR__ . '/data/http-client-without-timeout.php'],
            [
                [
                    'HTTP client call has no explicit timeout.',
                    42,
                ],
                [
                    'HTTP client call has no explicit timeout.',
                    43,
                ],
                [
                    'HTTP client call has no explicit timeout.',
                    47,
                ],
                [
                    'HTTP client call has no explicit timeout.',
                    50,
                ],
                [
                    'HTTP client call has no explicit timeout.',
                    52,
                ],
                [
                    'HTTP client call has no explicit timeout.',
                    57,
                ],
                [
                    'HTTP client call has no explicit timeout.',
                    77,
                ],
                [
                    'HTTP client call has no explicit timeout.',
                    88,
                ],
            ],
        );
    }
}
