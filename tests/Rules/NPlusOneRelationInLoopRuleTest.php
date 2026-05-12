<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanYiiPolicyRules\Rules\NPlusOneRelationInLoopRule;

/**
 * @extends RuleTestCase<NPlusOneRelationInLoopRule>
 */
final class NPlusOneRelationInLoopRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NPlusOneRelationInLoopRule();
    }

    public function testReportsRelationReadInLoopWithoutEagerLoading(): void
    {
        $this->analyse(
            [__DIR__ . '/data/n-plus-one-relation-in-loop.php'],
            [
                [
                    'Relation \'author\' is read in loop without with() or joinWith(); eager load it to avoid N+1 queries.',
                    53,
                ],
            ],
        );
    }
}
