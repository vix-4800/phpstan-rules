<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Greater;
use PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BinaryOp\Smaller;
use PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\VarLikeIdentifier;
use PhpParser\Node\VariadicPlaceholder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Vix\PhpstanYiiPolicyRules\Support\QueryChainInspector;

/**
 * @implements Rule<Expr>
 */
final readonly class QueryPerformanceSmellRule implements Rule
{
    private const string COUNT_ALL_MESSAGE = 'Use query count() instead of count(query->all()) or count(query->column()) to avoid loading rows into memory.';

    private const string ONE_NULL_MESSAGE = 'Use query exists() instead of loading one() and comparing it with null.';

    private const string COUNT_EXISTS_MESSAGE = 'Use query exists() instead of comparing count() with zero/one when only existence is needed.';

    private const string CURRENT_USER_FIND_ONE_MESSAGE = 'Use Yii::$app->user->identity instead of findOne() with the current user id.';

    private QueryChainInspector $queryChainInspector;

    public function __construct()
    {
        $this->queryChainInspector = new QueryChainInspector();
    }

    public function getNodeType(): string
    {
        return Expr::class;
    }

    /**
     * @param Node  $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof Expr) {
            return [];
        }

        if ($node instanceof FuncCall && $this->isCountOrSizeofLoadedQueryResult($node)) {
            return [$this->buildError(self::COUNT_ALL_MESSAGE, 'yii.queryCountLoadedResult')];
        }

        if ($node instanceof BinaryOp) {
            $existenceMessage = $this->getExistenceComparisonMessage($node);

            if ($existenceMessage !== null) {
                return [$this->buildError($existenceMessage, 'yii.queryExistsInsteadOfCountOrOne')];
            }
        }

        if ($node instanceof StaticCall && $this->isCurrentUserFindOne($node)) {
            return [$this->buildError(self::CURRENT_USER_FIND_ONE_MESSAGE, 'yii.currentUserFindOne')];
        }

        return [];
    }

    private function buildError(string $message, string $identifier): IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)
            ->identifier($identifier)
            ->build();
    }

    private function getExistenceComparisonMessage(BinaryOp $binaryOp): ?string
    {
        if ($this->isOneNullComparison($binaryOp)) {
            return self::ONE_NULL_MESSAGE;
        }

        if ($this->isCountExistenceComparison($binaryOp)) {
            return self::COUNT_EXISTS_MESSAGE;
        }

        return null;
    }

    private function isOneNullComparison(BinaryOp $binaryOp): bool
    {
        if (!$binaryOp instanceof Identical
            && !$binaryOp instanceof NotIdentical
            && !$binaryOp instanceof Equal
            && !$binaryOp instanceof NotEqual
        ) {
            return false;
        }

        return ($this->isQueryOneCall($binaryOp->left) && $this->isNull($binaryOp->right))
            || ($this->isNull($binaryOp->left) && $this->isQueryOneCall($binaryOp->right));
    }

    private function isCountExistenceComparison(BinaryOp $binaryOp): bool
    {
        $leftCount = $this->isQueryCountExpression($binaryOp->left);
        $rightCount = $this->isQueryCountExpression($binaryOp->right);

        if ($leftCount === $rightCount) {
            return false;
        }

        if ($leftCount) {
            return $this->isExistenceThresholdComparison($binaryOp, $binaryOp->right, false);
        }

        return $this->isExistenceThresholdComparison($binaryOp, $binaryOp->left, true);
    }

    private function isExistenceThresholdComparison(BinaryOp $binaryOp, Expr $threshold, bool $mirrored): bool
    {
        if (!$threshold instanceof Int_) {
            return false;
        }

        $value = $threshold->value;

        if ($binaryOp instanceof NotEqual || $binaryOp instanceof NotIdentical) {
            return $value === 0;
        }

        if ($binaryOp instanceof Equal || $binaryOp instanceof Identical) {
            return $value === 0;
        }

        if ($mirrored) {
            return ($binaryOp instanceof Smaller && $value === 0)
                || ($binaryOp instanceof SmallerOrEqual && $value === 1)
                || ($binaryOp instanceof Greater && $value === 1)
                || ($binaryOp instanceof GreaterOrEqual && $value === 0);
        }

        return ($binaryOp instanceof Greater && $value === 0)
            || ($binaryOp instanceof GreaterOrEqual && $value === 1)
            || ($binaryOp instanceof Smaller && $value === 1)
            || ($binaryOp instanceof SmallerOrEqual && $value === 0);
    }

    private function isQueryCountExpression(Expr $expr): bool
    {
        if ($expr instanceof MethodCall && $this->isNamedMethodCall($expr, 'count')) {
            return $this->queryChainInspector->isQueryCall($expr, ['count']);
        }

        return false;
    }

    private function isCountOrSizeofLoadedQueryResult(FuncCall $funcCall): bool
    {
        if (!$funcCall->name instanceof Name) {
            return false;
        }

        $functionName = mb_strtolower($funcCall->name->toString());

        if ($functionName !== 'count' && $functionName !== 'sizeof') {
            return false;
        }

        $firstArgument = $this->getArgument($funcCall->args, 0);

        return $firstArgument instanceof Arg
            && $firstArgument->value instanceof MethodCall
            && $this->queryChainInspector->isQueryCall($firstArgument->value, ['all', 'column']);
    }

    private function isQueryOneCall(Expr $expr): bool
    {
        return $expr instanceof MethodCall
            && $this->queryChainInspector->isQueryCall($expr, ['one']);
    }

    private function isCurrentUserFindOne(StaticCall $staticCall): bool
    {
        if (!$staticCall->name instanceof Identifier || $staticCall->name->toString() !== 'findOne') {
            return false;
        }

        $firstArgument = $this->getArgument($staticCall->args, 0);

        return $firstArgument instanceof Arg && $this->containsYiiCurrentUserReference($firstArgument->value);
    }

    /**
     * @param array<Arg|VariadicPlaceholder> $args
     */
    private function getArgument(array $args, int $index): ?Arg
    {
        $arg = $args[$index] ?? null;

        return $arg instanceof Arg ? $arg : null;
    }

    private function containsYiiCurrentUserReference(Expr $expr): bool
    {
        if ($this->isYiiCurrentUserExpression($expr)) {
            return true;
        }

        if ($expr instanceof PropertyFetch) {
            return $expr->var instanceof Expr && $this->containsYiiCurrentUserReference($expr->var);
        }

        if ($expr instanceof MethodCall) {
            return $expr->var instanceof Expr && $this->containsYiiCurrentUserReference($expr->var);
        }

        if ($expr instanceof Array_) {
            foreach ($expr->items as $item) {
                if ($item !== null && $this->containsYiiCurrentUserReference($item->value)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isYiiCurrentUserExpression(Expr $expr): bool
    {
        if (!$expr instanceof PropertyFetch && !$expr instanceof MethodCall) {
            return false;
        }

        $name = $expr->name instanceof Identifier ? $expr->name->toString() : null;

        if ($name !== 'user' && $name !== 'getUser') {
            return false;
        }

        $var = $expr->var;

        return $var instanceof StaticPropertyFetch
            && $var->class instanceof Name
            && mb_ltrim($var->class->toString(), '\\') === 'Yii'
            && $var->name instanceof VarLikeIdentifier
            && $var->name->toString() === 'app';
    }

    private function isNamedMethodCall(MethodCall $methodCall, string $methodName): bool
    {
        return $methodCall->name instanceof Identifier && $methodCall->name->toString() === $methodName;
    }

    private function isNull(Expr $expr): bool
    {
        return $expr instanceof ConstFetch && mb_strtolower($expr->name->toString()) === 'null';
    }
}
