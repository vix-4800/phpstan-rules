<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Vix\PhpstanYiiPolicyRules\Support\YiiControllerRuleHelper;

/**
 * @implements Rule<Class_>
 */
final readonly class NativeHeaderInControllerRule implements Rule
{
    private YiiControllerRuleHelper $helper;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->helper = new YiiControllerRuleHelper($reflectionProvider);
    }

    public function getNodeType(): string
    {
        return Class_::class;
    }

    /**
     * @param Node  $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof Class_) {
            return [];
        }

        if (!$this->helper->isYiiController($node, $scope)) {
            return [];
        }

        $finder = new NodeFinder();
        $errors = [];

        foreach ($finder->findInstanceOf($node->stmts, FuncCall::class) as $funcCall) {
            if (!$funcCall->name instanceof Name || mb_strtolower($funcCall->name->toString()) !== 'header') {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(
                'Do not call native header() inside Yii controllers; use response component or asJson().',
            )
                ->identifier('yii.nativeHeaderInController')
                ->line($funcCall->getStartLine())
                ->build();
        }

        return $errors;
    }
}
