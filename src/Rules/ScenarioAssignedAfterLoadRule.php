<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<ClassMethod>
 */
final readonly class ScenarioAssignedAfterLoadRule implements Rule
{
    private const array MASS_ASSIGNMENT_METHODS = ['load', 'setAttributes'];

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @param Node  $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof ClassMethod) {
            return [];
        }

        return $this->processStatements(array_values($node->stmts ?? []), []);
    }

    /**
     * @param list<Stmt>          $statements
     * @param array<string, bool> $loadedVariables
     *
     * @return list<IdentifierRuleError>
     */
    private function processStatements(array $statements, array $loadedVariables): array
    {
        $errors = [];

        foreach ($statements as $statement) {
            foreach ($statement->getSubNodeNames() as $subNodeName) {
                $subNode = $statement->{$subNodeName};

                if ($subNode instanceof Expr) {
                    $errors = [
                        ...$errors,
                        ...$this->processExpression($subNode, $loadedVariables),
                    ];
                }
            }
        }

        return $errors;
    }

    /**
     * @param array<string, bool> $loadedVariables
     *
     * @return list<IdentifierRuleError>
     */
    private function processExpression(Expr $expr, array &$loadedVariables): array
    {
        $errors = [];

        if ($expr instanceof Assign) {
            if ($expr->var instanceof Variable && $expr->expr instanceof New_) {
                unset($loadedVariables[$this->variableName($expr->var)]);
            }

            if ($this->isAttributesAssignment($expr)) {
                $loadedVariables[$this->modelVariableName($expr->var)] = true;
            }

            if ($this->isScenarioAssignment($expr)) {
                $variableName = $this->modelVariableName($expr->var);

                if (($loadedVariables[$variableName] ?? false) === true) {
                    $errors[] = $this->buildError($expr);
                }
            }

            return [
                ...$errors,
                ...$this->processExpression($expr->expr, $loadedVariables),
            ];
        }

        if ($expr instanceof MethodCall && $this->isModelMethodCall($expr)) {
            $variableName = $this->modelVariableName($expr->var);

            if ($this->isScenarioSetter($expr) && ($loadedVariables[$variableName] ?? false) === true) {
                $errors[] = $this->buildError($expr);
            }

            if ($this->isMassAssignmentCall($expr)) {
                $loadedVariables[$variableName] = true;
            }
        }

        foreach ($expr->getSubNodeNames() as $subNodeName) {
            $subNode = $expr->{$subNodeName};

            if ($subNode instanceof Expr) {
                $errors = [
                    ...$errors,
                    ...$this->processExpression($subNode, $loadedVariables),
                ];
            }
        }

        return $errors;
    }

    private function buildError(Node $node): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            'Assign model scenario before load(), setAttributes(), or attributes mass assignment.',
        )
            ->identifier('yii.scenarioAssignedAfterLoad')
            ->line($node->getStartLine())
            ->build();
    }

    private function isMassAssignmentCall(MethodCall $call): bool
    {
        return $call->name instanceof Identifier
            && in_array($call->name->toString(), self::MASS_ASSIGNMENT_METHODS, true);
    }

    private function isScenarioSetter(MethodCall $call): bool
    {
        return $call->name instanceof Identifier && $call->name->toString() === 'setScenario';
    }

    private function isModelMethodCall(MethodCall $call): bool
    {
        return $call->var instanceof Variable && is_string($call->var->name);
    }

    private function isAttributesAssignment(Assign $assign): bool
    {
        return $assign->var instanceof PropertyFetch
            && $assign->var->var instanceof Variable
            && $assign->var->name instanceof Identifier
            && $assign->var->name->toString() === 'attributes';
    }

    private function isScenarioAssignment(Assign $assign): bool
    {
        return $assign->var instanceof PropertyFetch
            && $assign->var->var instanceof Variable
            && $assign->var->name instanceof Identifier
            && $assign->var->name->toString() === 'scenario';
    }

    private function modelVariableName(Expr $expr): string
    {
        if ($expr instanceof PropertyFetch) {
            return $this->modelVariableName($expr->var);
        }

        if ($expr instanceof Variable) {
            return $this->variableName($expr);
        }

        return '';
    }

    private function variableName(Variable $variable): string
    {
        return is_string($variable->name) ? $variable->name : '';
    }
}
