<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Expr>
 */
final class DisabledSslVerificationRule implements Rule
{
    private const string MESSAGE = 'SSL certificate verification must not be disabled.';

    public function getNodeType(): string
    {
        return Expr::class;
    }

    /**
     * @param Expr  $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node instanceof FuncCall && NodeHelpers::isFunctionCall($node, 'curl_setopt')) {
            return $this->processCurlSetopt($node);
        }

        if ($node instanceof FuncCall && NodeHelpers::isFunctionCall($node, 'curl_setopt_array')) {
            return $this->processCurlSetoptArray(NodeHelpers::argAt($node->args, 1));
        }

        if (($node instanceof MethodCall || $node instanceof StaticCall) && NodeHelpers::isRequestCall($node)) {
            $options = NodeHelpers::argAt($node->getArgs(), 2);

            return $options !== null
                && NodeHelpers::optionArrayHasKey($options, 'verify')
                && $this->isVerifyFalse($options->value)
                    ? [$this->error()]
                    : [];
        }

        return [];
    }

    /**
     * @param FuncCall $node
     *
     * @return list<IdentifierRuleError>
     */
    private function processCurlSetopt(FuncCall $node): array
    {
        $option = NodeHelpers::argAt($node->args, 1);
        $value = NodeHelpers::argAt($node->args, 2);

        if ($option === null || $value === null) {
            return [];
        }

        if ($this->isSslVerifyPeer($option->value) && NodeHelpers::isFalseLike($value->value)) {
            return [$this->error()];
        }

        if ($this->isSslVerifyHost($option->value) && NodeHelpers::isZeroLike($value->value)) {
            return [$this->error()];
        }

        return [];
    }

    /**
     * @param ?Arg $arg
     *
     * @return list<IdentifierRuleError>
     */
    private function processCurlSetoptArray(?Arg $arg): array
    {
        if ($arg === null || !$arg->value instanceof Array_) {
            return [];
        }

        foreach ($arg->value->items as $item) {
            if ($item->key === null) {
                continue;
            }

            if ($this->isSslVerifyPeer($item->key) && NodeHelpers::isFalseLike($item->value)) {
                return [$this->error()];
            }

            if ($this->isSslVerifyHost($item->key) && NodeHelpers::isZeroLike($item->value)) {
                return [$this->error()];
            }
        }

        return [];
    }

    private function isVerifyFalse(Expr $expr): bool
    {
        if (!$expr instanceof Array_) {
            return false;
        }

        foreach ($expr->items as $item) {
            if ($item->key === null) {
                continue;
            }

            if (
                $item->key instanceof String_
                && mb_strtolower($item->key->value) === 'verify'
                && NodeHelpers::isFalseLike($item->value)
            ) {
                return true;
            }
        }

        return false;
    }

    private function isSslVerifyPeer(Expr $expr): bool
    {
        return $expr instanceof ConstFetch
            && NodeHelpers::nameEquals($expr->name, 'CURLOPT_SSL_VERIFYPEER');
    }

    private function isSslVerifyHost(Expr $expr): bool
    {
        return $expr instanceof ConstFetch
            && NodeHelpers::nameEquals($expr->name, 'CURLOPT_SSL_VERIFYHOST');
    }

    private function error(): IdentifierRuleError
    {
        return RuleErrorBuilder::message(self::MESSAGE)
            ->identifier('vix.disabledSslVerification')
            ->build();
    }
}
