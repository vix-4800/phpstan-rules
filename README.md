# PHPStan Yii Policy Rules

PHPStan extension with policy checks for Yii2 projects.

## Setup

Include extension config in PHPStan config:

```neon
includes:
    - vendor/vix/phpstan-yii-policy-rules/extension.neon
```

Optional config:

```neon
parameters:
    yiiPolicy:
        allowedSaveFalseNamespaces:
            - app\migrations
```

## Rules

### `yii.missingAccessRule`

Checks Yii controller actions for matching `yii\filters\AccessControl` behavior.

Public methods named `action*` must be covered by `AccessControl`. Rule respects behavior `only` and `except`, and also checks nested `rules[*].actions` when present.

### `yii.missingVerbFilterRule`

Checks Yii controller actions for matching `yii\filters\VerbFilter` behavior.

Public methods named `action*` must be listed in behavior `actions`. Rule respects behavior `only` and `except`.

### `yii.missingAjaxFilterRule`

Checks AJAX-style Yii controller actions for matching `yii\filters\AjaxFilter` behavior.

Action is treated as AJAX endpoint when method body calls `asJson()` or references `yii\web\Response::FORMAT_JSON`. Rule respects behavior `only` and `except`.

### `yii.massSelectionWithoutLimit`

Reports unbounded Active Record query chains ending with `find()->all()`.

Use `limit()` or `page()` before `all()` when loading records from `find()`.

### `yii.saveFalseWithoutReason`

Reports calls to `save(false)`. Calls without explicit attribute list are reported as higher risk than
`save(false, ['field'])`.

Use validation, or place explicitly allowed namespaces in `yiiPolicy.allowedSaveFalseNamespaces` when validation bypass is expected.
Namespaces containing `migrations`, `tests`, `seeders`, or `seeds` are allowed.

### `yii.publicAllowWithoutConstraint`

Reports `AccessControl` rules with `'allow' => true` but without `roles`, `permissions`, `matchCallback`, `ips`,
`verbs`, or `actions`.

### `yii.mutatingActionAllowsGet`

Reports mutating controller actions whose configured `VerbFilter` allows `GET`/`HEAD` or omits a mutating HTTP verb.

### `yii.rawSqlConditionWithVariable`

Reports `where()`, `andWhere()`, `orWhere()`, and `having()` raw SQL strings built with interpolation or concatenated variables.

### `yii.queryOneWithoutLimit`

Reports ActiveQuery/Query chains ending with `one()` without explicit `limit(1)`.

### `yii.redirectReferrerWithoutFallback`

Reports `redirect($request->referrer)` and `$referrer` redirects without a fallback route.
