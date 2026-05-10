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

Reports calls to `save(false)`.

Use validation, or place explicitly allowed namespaces in `yiiPolicy.allowedSaveFalseNamespaces` when validation bypass is expected.
