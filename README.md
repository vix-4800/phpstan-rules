# PHPStan Yii Policy Rules

PHPStan extension with policy checks for Yii2 projects.

- [PHPStan Yii Policy Rules](#phpstan-yii-policy-rules)
  - [Setup](#setup)
  - [Rules](#rules)
    - [`yii.missingAccessRule`](#yiimissingaccessrule)
    - [`yii.missingVerbFilterRule`](#yiimissingverbfilterrule)
    - [`yii.missingAjaxFilterRule`](#yiimissingajaxfilterrule)
    - [`yii.unknownActionInBehavior`](#yiiunknownactioninbehavior)
    - [`yii.massSelectionWithoutLimit`](#yiimassselectionwithoutlimit)
    - [`yii.saveFalseWithoutReason`](#yiisavefalsewithoutreason)
    - [`yii.lifecycleParentCall`](#yiilifecycleparentcall)
    - [`yii.componentInitParentCall`](#yiicomponentinitparentcall)
    - [`yii.lifecycleSelfSave`](#yiilifecycleselfsave)
    - [`yii.publicAllowWithoutConstraint`](#yiipublicallowwithoutconstraint)
    - [`yii.mutatingActionAllowsGet`](#yiimutatingactionallowsget)
    - [`yii.csrfDisabledWithoutCompensatingControl`](#yiicsrfdisabledwithoutcompensatingcontrol)
    - [`yii.rawSqlConditionWithVariable`](#yiirawsqlconditionwithvariable)
    - [`yii.deleteAllOrUpdateAllWithoutWhere`](#yiideleteallorupdateallwithoutwhere)
    - [`yii.transactionWithoutRollbackHandling`](#yiitransactionwithoutrollbackhandling)
    - [`yii.queryOneWithoutLimit`](#yiiqueryonewithoutlimit)
    - [`yii.redirectReferrerWithoutFallback`](#yiiredirectreferrerwithoutfallback)
    - [`yii.nativeHeaderInController`](#yiinativeheaderincontroller)
    - [`yii.mixedResponseTypesInAction`](#yiimixedresponsetypesinaction)
    - [`yii.unboundedQueryResult`](#yiiunboundedqueryresult)

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

### `yii.unknownActionInBehavior`

Reports behavior action references that do not match a controller `action*()` method or a literal external action key from `actions()`.

Checks behavior `only` / `except`, `AccessControl rules[*].actions`, `VerbFilter::actions`, and `AjaxFilter::only`.

### `yii.massSelectionWithoutLimit`

Reports unbounded Active Record query chains ending with `find()->all()`.

Use `limit()` or `page()` before `all()` when loading records from `find()`.

### `yii.saveFalseWithoutReason`

Reports calls to `save(false)`. Calls without explicit attribute list are reported as higher risk than
`save(false, ['field'])`.

Use validation, or place explicitly allowed namespaces in `yiiPolicy.allowedSaveFalseNamespaces` when validation bypass is expected.
Namespaces containing `migrations`, `tests`, `seeders`, or `seeds` are allowed.

### `yii.lifecycleParentCall`

Reports Active Record overrides of `beforeValidate()`, `beforeSave()`, `afterSave()`, `afterFind()`, and `afterDelete()` that do not call the matching `parent::*()` method.

Skipping the parent call can break Yii events, attached behaviors, and audit hooks.

### `yii.componentInitParentCall`

Reports `init()` overrides in Yii `Component`, `Widget`, `Behavior`, and `AssetBundle` subclasses that do not call `parent::init()`.

This catches Yii-specific initialization bugs that generic PHP static analysis does not understand.

### `yii.lifecycleSelfSave`

Reports `$this->save()`, `$this->update()`, and `$this->delete()` inside Active Record lifecycle hooks.

These self-mutations are high risk because they can recurse, trigger duplicate events, or leave model state inconsistent.

### `yii.publicAllowWithoutConstraint`

Reports `AccessControl` rules with `'allow' => true` but without `roles`, `permissions`, `matchCallback`, `ips`,
`verbs`, or `actions`.

### `yii.mutatingActionAllowsGet`

Reports mutating controller actions whose configured `VerbFilter` allows `GET`/`HEAD` or omits a mutating HTTP verb.

Mutation detection covers common Active Record writes such as `updateAttributes()`, `updateCounters()`, `updateAllCounters()`, `insert(false)`, `update(false)`, and common filesystem writes such as `rename()`, `unlink()`, `mkdir()`, and `rmdir()`.

### `yii.csrfDisabledWithoutCompensatingControl`

Reports `$this->enableCsrfValidation = false` inside controller actions and `beforeAction()`.

Use only for endpoints with an explicit compensating control such as signature verification or another boundary check.

### `yii.rawSqlConditionWithVariable`

Reports raw SQL strings built with interpolation or concatenated variables in `where()`-style conditions, `join()` / `on()`, `from()`, `orderBy()`, and `createCommand()`.

### `yii.deleteAllOrUpdateAllWithoutWhere`

Reports `deleteAll()` and `updateAll()` calls without a condition argument, or with an empty condition such as `''`, `[]`, or `null`.

### `yii.transactionWithoutRollbackHandling`

Reports methods that call `beginTransaction()` but do not call `rollBack()` / `rollback()` from a `catch` block.

### `yii.queryOneWithoutLimit`

Reports ActiveQuery/Query chains ending with `one()` without explicit `limit(1)`.

### `yii.redirectReferrerWithoutFallback`

Reports `redirect($request->referrer)` and `$referrer` redirects without a fallback route.

### `yii.nativeHeaderInController`

Reports native `header()` calls inside Yii controllers.

### `yii.mixedResponseTypesInAction`

Reports controller actions that return JSON via `asJson()` in one path and non-JSON responses via `render*()` or `redirect()` in another path.

### `yii.unboundedQueryResult`

Reports ActiveQuery/Query result chains ending with `all()` or `column()` without a bounding or streaming strategy.

The rule treats `batch()`, `each()`, `exists()`, `count()`, and DataProvider usage as safe alternatives when they are the intended access pattern.
