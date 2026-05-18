<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
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

        if (($node instanceof MethodCall || $node instanceof StaticCall) && NodeHelpers::isRequestCall($node)) {
            return $this->hasTimeoutOption(NodeHelpers::argAt($node->getArgs(), 2)) ? [] : [$this->error()];
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

        return $this->hasTimeoutOption(NodeHelpers::argAt($node->args, 0)) ? [] : [$this->error()];
    }

    /**
     * @param array<Stmt> $statements
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

                $errors[] = $this->error($expr->getLine());
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
            $option = NodeHelpers::argAt($expr->args, 1);

            return $option !== null && $this->isCurlTimeoutConstant($option->value);
        }

        if (NodeHelpers::nameEquals($expr->name, 'curl_setopt_array')) {
            $options = NodeHelpers::argAt($expr->args, 1);

            return $options !== null
                && $options->value instanceof Array_
                && $this->curlOptionArrayHasTimeout($options->value);
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
            fn(ArrayItem $item): bool => $item->key !== null && $this->isCurlTimeoutConstant($item->key),
        );
    }

    private function firstArgKey(Expr $expr): string
    {
        if (!$expr instanceof FuncCall) {
            return '';
        }

        $firstArg = NodeHelpers::argAt($expr->args, 0);

        if ($firstArg === null) {
            return '';
        }

        $arg = $firstArg->value;

        if ($arg instanceof Variable && is_string($arg->name)) {
            return '$' . $arg->name;
        }

        return spl_object_hash($arg);
    }

    private function error(?int $line = null): IdentifierRuleError
    {
        $builder = RuleErrorBuilder::message(self::MESSAGE)
            ->identifier('vix.httpClientWithoutTimeout');

        if ($line !== null) {
            $builder->line($line);
        }

        return $builder->build();
    }
}
