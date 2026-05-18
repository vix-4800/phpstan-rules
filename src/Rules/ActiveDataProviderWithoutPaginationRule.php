<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\VariadicPlaceholder;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Vix\PhpstanRules\Support\YiiClassHierarchy;

/**
 * @implements Rule<Class_>
 */
final readonly class ActiveDataProviderWithoutPaginationRule implements Rule
{
    private const array DATA_PROVIDER_CLASSES = [
        'yii\data\ActiveDataProvider',
        'yii\data\SqlDataProvider',
    ];

    private const array WEB_CONTEXT_CLASSES = [
        'yii\base\Controller',
        'yii\web\Controller',
        'yii\rest\Controller',
        'yii\base\Action',
    ];

    private YiiClassHierarchy $classHierarchy;

    public function __construct(
        ReflectionProvider $reflectionProvider,
    ) {
        $this->classHierarchy = new YiiClassHierarchy($reflectionProvider);
    }

    public function getNodeType(): string
    {
        return Class_::class;
    }

    /**
     * @param Class_ $node
     * @param Scope  $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->classHierarchy->isSubclassOfAny($node, $scope, self::WEB_CONTEXT_CLASSES)) {
            return [];
        }

        $errors = [];
        $finder = new NodeFinder();

        foreach ($finder->findInstanceOf($node->getMethods(), New_::class) as $new) {
            if (!$this->isDataProvider($new)) {
                continue;
            }

            $config = $this->getArgument($new->args, 0);

            if (!$config instanceof Arg) {
                continue;
            }

            if (!$config->value instanceof Array_) {
                continue;
            }

            if (!$this->hasDisabledPagination($config->value)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(
                'Do not disable pagination for ActiveDataProvider or SqlDataProvider in web context.',
            )
                ->identifier('yii.activeDataProviderWithoutPagination')
                ->line($new->getStartLine())
                ->build();
        }

        return $errors;
    }

    private function isDataProvider(New_ $new): bool
    {
        if (!$new->class instanceof Name) {
            return false;
        }

        $className = mb_ltrim($new->class->toString(), '\\');
        $resolvedName = $new->class->getAttribute('resolvedName');

        if ($resolvedName instanceof Name) {
            $className = mb_ltrim($resolvedName->toString(), '\\');
        }

        return in_array($className, self::DATA_PROVIDER_CLASSES, true)
            || in_array($new->class->toString(), ['ActiveDataProvider', 'SqlDataProvider'], true);
    }

    private function hasDisabledPagination(Array_ $config): bool
    {
        foreach ($config->items as $item) {
            if (!$item->key instanceof String_) {
                continue;
            }

            if ($item->key->value !== 'pagination') {
                continue;
            }

            return $item->value instanceof ConstFetch && strcasecmp($item->value->name->toString(), 'false') === 0;
        }

        return false;
    }

    /**
     * @param array<int|string, Arg|VariadicPlaceholder> $args
     * @param int                                        $index
     */
    private function getArgument(array $args, int $index): ?Arg
    {
        $arg = $args[$index] ?? null;

        return $arg instanceof Arg ? $arg : null;
    }
}
