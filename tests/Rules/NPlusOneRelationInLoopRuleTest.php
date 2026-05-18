<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Tests\Rules;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Vix\PhpstanRules\Rules\NPlusOneRelationInLoopRule;

/**
 * @extends RuleTestCase<NPlusOneRelationInLoopRule>
 */
final class NPlusOneRelationInLoopRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NPlusOneRelationInLoopRule(
            self::getContainer()->getByType(ReflectionProvider::class),
        );
    }

    public function testReportsRelationReadInLoopWithoutEagerLoading(): void
    {
        require_once __DIR__ . '/data/n-plus-one-external-model.php';

        $this->analyse(
            [
                __DIR__ . '/data/n-plus-one-external-model.php',
                __DIR__ . '/data/n-plus-one-relation-in-loop.php',
            ],
            [
                [
                    'Relation \'author\' is read in loop without with() or joinWith(); eager load it to avoid N+1 queries.',
                    55,
                ],
                [
                    'Relation \'author\' is read in loop without with() or joinWith(); eager load it to avoid N+1 queries.',
                    108,
                ],
            ],
        );
    }
}
