<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Rules;

use Node\Stmt;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node>
 */
final class HttpClientWithoutTimeoutRule implements Rule
{
    private const string MESSAGE = 'HTTP client call has no explicit timeout.';

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node instanceof New_) {
            return $this->processNewClient($node);
        }

        if ($node instanceof Expr && NodeHelpers::isRequestCall($node)) {
            return $this->hasTimeoutOption($node->args[2] ?? null) ? [] : [$this->error()];
        }

        if ($node instanceof ClassMethod || $node instanceof Function_) {
            return $this->processStatements($node->stmts ?? []);
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function processNewClient(New_ $node): array
    {
        if (!$node->class instanceof Name || !NodeHelpers::classNameEndsWith($node->class, 'Client')) {
            return [];
        }

        return $this->hasTimeoutOption($node->args[0] ?? null) ? [] : [$this->error()];
    }

    /**
     * @param list<Stmt> $statements
     *
     * @return list<IdentifierRuleError>
     */
    private function processStatements(array $statements): array
    {
        $errors = [];
        $curlTimeoutVariables = [];

        foreach ($statements as $statement) {
            foreach ($this->expressionsFrom($statement) as $expr) {
                if ($this->isCurlTimeoutSet($expr)) {
                    $curlTimeoutVariables[$this->firstArgKey($expr)] = true;
                }

                if (!$this->isCurlExecWithoutTimeout($expr, $curlTimeoutVariables)) {
                    continue;
                }

                $errors[] = $this->error();
            }
        }

        return $errors;
    }

    /**
     * @return list<Expr>
     */
    private function expressionsFrom(Node $node): array
    {
        $expressions = [];

        if ($node instanceof Expr) {
            $expressions[] = $node;
        }

        foreach ($node->getSubNodeNames() as $name) {
            $subNode = $node->$name;

            if ($subNode instanceof Node) {
                array_push($expressions, ...$this->expressionsFrom($subNode));

                continue;
            }

            if (!is_array($subNode)) {
                continue;
            }

            foreach ($subNode as $child) {
                if (!($child instanceof Node)) {
                    continue;
                }

                array_push($expressions, ...$this->expressionsFrom($child));
            }
        }

        return $expressions;
    }

    private function hasTimeoutOption(?Arg $arg): bool
    {
        if (NodeHelpers::optionArrayHasKey($arg, 'timeout')) {
            return true;
        }

        return NodeHelpers::optionArrayHasKey($arg, 'connect_timeout');
    }

    private function isCurlTimeoutSet(Expr $expr): bool
    {
        if (!$expr instanceof FuncCall || !$expr->name instanceof Name) {
            return false;
        }

        if (NodeHelpers::nameEquals($expr->name, 'curl_setopt')) {
            return isset($expr->args[1])
                && $this->isCurlTimeoutConstant($expr->args[1]->value);
        }

        if (NodeHelpers::nameEquals($expr->name, 'curl_setopt_array')) {
            return isset($expr->args[1])
                && $expr->args[1]->value instanceof Array_
                && $this->curlOptionArrayHasTimeout($expr->args[1]->value);
        }

        return false;
    }

    /**
     * @param array<string, true> $curlTimeoutVariables
     */
    private function isCurlExecWithoutTimeout(Expr $expr, array $curlTimeoutVariables): bool
    {
        return NodeHelpers::isFunctionCall($expr, 'curl_exec')
            && !isset($curlTimeoutVariables[$this->firstArgKey($expr)]);
    }

    private function isCurlTimeoutConstant(Expr $expr): bool
    {
        return $expr instanceof ConstFetch
            && in_array($expr->name->toString(), [
                'CURLOPT_TIMEOUT',
                'CURLOPT_TIMEOUT_MS',
                'CURLOPT_CONNECTTIMEOUT',
                'CURLOPT_CONNECTTIMEOUT_MS',
            ], true);
    }

    private function curlOptionArrayHasTimeout(Array_ $array): bool
    {
        return array_any(
            $array->items,
            fn(ArrayItem $item): bool => $item?->key !== null && $this->isCurlTimeoutConstant($item->key),
        );
    }

    private function firstArgKey(Expr $expr): string
    {
        if (!$expr instanceof FuncCall || !isset($expr->args[0])) {
            return '';
        }

        $arg = $expr->args[0]->value;

        if ($arg instanceof Variable && is_string($arg->name)) {
            return '$' . $arg->name;
        }

        return spl_object_hash($arg);
    }

    private function error(): IdentifierRuleError
    {
        return RuleErrorBuilder::message(self::MESSAGE)
            ->identifier('vix.httpClientWithoutTimeout')
            ->build();
    }
}
