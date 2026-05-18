# PHPStan Rules

PHPStan rules package with framework-specific rule sets.

[![Tests](https://github.com/vix-4800/phpstan-rules/actions/workflows/php.yml/badge.svg)](https://github.com/vix-4800/phpstan-rules/actions/workflows/php.yml)
[![PHPStan](https://github.com/vix-4800/phpstan-rules/actions/workflows/phpstan.yml/badge.svg)](https://github.com/vix-4800/phpstan-rules/actions/workflows/phpstan.yml)
[![PHP Version](https://img.shields.io/badge/php-%5E8.4-blue)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

- [PHPStan Rules](#phpstan-rules)
  - [Setup](#setup)
  - [Versioning](#versioning)
  - [Yii2 Rules](#yii2-rules)
    - [`yii.missingAccessRule`](#yiimissingaccessrule)
    - [`yii.missingVerbFilterRule`](#yiimissingverbfilterrule)
    - [`yii.missingAjaxFilterRule`](#yiimissingajaxfilterrule)
    - [`yii.unknownActionInBehavior`](#yiiunknownactioninbehavior)
    - [`yii.massSelectionWithoutLimit`](#yiimassselectionwithoutlimit)
    - [`yii.saveFalseWithoutReason`](#yiisavefalsewithoutreason)
    - [`yii.fileValidatorTooLoose`](#yiifilevalidatortooloose)
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
    - [`yii.queryPerformanceSmell`](#yiiqueryperformancesmell)
    - [`yii.imageValidatorTooLoose`](#yiiimagevalidatortooloose)
    - [`yii.activeDataProviderWithoutPagination`](#yiiactivedataproviderwithoutpagination)
    - [`yii.controllerBeforeActionParentResultIgnored`](#yiicontrollerbeforeactionparentresultignored)
    - [`yii.nPlusOneRelationInLoop`](#yiinplusonerelationinloop)
    - [`yii.scenarioAssignedAfterLoad`](#yiiscenarioassignedafterload)
    - [`yii.sensitiveAttributeMarkedSafe`](#yiisensitiveattributemarkedsafe)

## Setup

Install as a development dependency:

```bash
composer require --dev vix/phpstan-rules
```

Include extension config in PHPStan config:

```neon
includes:
    - vendor/vix/phpstan-rules/extension.neon
```

When `phpstan/extension-installer` is installed, `extension.neon` is loaded automatically.

Enable all Yii2 rules:

```neon
parameters:
    phpstanRules:
        yii2:
            rules:
                all: true
```

Or enable selected Yii2 rules:

```neon
parameters:
    phpstanRules:
        yii2:
            rules:
                missingAccessRule: true
                missingVerbFilterRule: true
                queryOneWithoutLimit: true
```

Default Yii2 config from `extension.neon`:

```neon
parameters:
    phpstanRules:
        yii2:
            rules:
                activeDataProviderWithoutPagination: false
                all: false
                componentInitParentCall: false
                controllerBeforeActionParentResultIgnored: false
                csrfDisabledWithoutCompensatingControl: false
                deleteAllOrUpdateAllWithoutWhere: false
                fileValidatorTooLoose: false
                imageValidatorTooLoose: false
                lifecycleParentCall: false
                lifecycleSelfSave: false
                massSelectionWithoutLimit: false
                missingAccessRule: false
                missingAjaxFilterRule: false
                missingVerbFilterRule: false
                mixedResponseTypesInAction: false
                mutatingActionAllowsGet: false
                nPlusOneRelationInLoop: false
                nativeHeaderInController: false
                publicAllowWithoutConstraint: false
                queryOneWithoutLimit: false
                queryPerformanceSmell: false
                rawSqlConditionWithVariable: false
                redirectReferrerWithoutFallback: false
                saveFalseWithoutReason: false
                scenarioAssignedAfterLoad: false
                sensitiveAttributeMarkedSafe: false
                transactionWithoutRollbackHandling: false
                unboundedQueryResult: false
                unknownActionInBehavior: false
            allowedSaveFalseNamespaces: []
            sensitiveAttributePatterns:
                - '~^(id|user_id|created_at|updated_at|created_by|updated_by|role|status|...|is_admin)$~i'
```

Override example:

```neon
parameters:
    phpstanRules:
        yii2:
            allowedSaveFalseNamespaces:
                - app\migrations
```

## Versioning

This package follows Semantic Versioning.

- `main` is aliased to `0.1.x-dev` until the first stable release line moves forward.
- The first stable tag for this package should be `v0.1.0`.
- Stable installs should use a constraint such as `^0.1` once `v0.1.0` is tagged.
- Before the first stable tag exists, use the development constraint `0.1.x-dev`.

Example constraints:

```bash
composer require --dev vix/phpstan-rules:^0.1
composer require --dev vix/phpstan-rules:0.1.x-dev
```

## Yii2 Rules

### `yii.missingAccessRule`

Checks Yii controller actions for matching `yii\filters\AccessControl` behavior.

Public methods named `action*` must be covered by `AccessControl`.
Rule respects behavior `only` and `except`, and also checks nested `rules[*].actions` when present.

### `yii.missingVerbFilterRule`

Checks Yii controller actions for matching `yii\filters\VerbFilter` behavior.

Public methods named `action*` must be listed in behavior `actions`. Rule respects behavior `only` and `except`.

### `yii.missingAjaxFilterRule`

Checks AJAX-style Yii controller actions for matching `yii\filters\AjaxFilter` behavior.

Action is treated as AJAX endpoint when method body calls `asJson()`
or references `yii\web\Response::FORMAT_JSON`. Rule respects behavior `only` and `except`.

### `yii.unknownActionInBehavior`

Reports behavior action references that do not match a controller `action*()` method or a literal external action key from `actions()`.

Checks behavior `only` / `except`, `AccessControl rules[*].actions`, `VerbFilter::actions`, and `AjaxFilter::only`.

### `yii.massSelectionWithoutLimit`

Reports unbounded Active Record query chains ending with `find()->all()`.

Use `limit()` or `page()` before `all()` when loading records from `find()`.

### `yii.saveFalseWithoutReason`

Reports calls to `save(false)`. Calls without explicit attribute list are reported as higher risk than
`save(false, ['field'])`.

Use validation, or place explicitly allowed namespaces in `phpstanRules.yii2.allowedSaveFalseNamespaces` when validation bypass is expected.
Namespaces containing `migrations`, `tests`, `seeders`, or `seeds` are allowed.

### `yii.fileValidatorTooLoose`

Reports Yii model `file` validators declared without at least one of `extensions` or `mimeTypes`.

This catches overly broad upload rules such as `[['file'], 'file']`. `maxSize` is still recommended, but it is not enough on its own.

### `yii.lifecycleParentCall`

Reports Active Record overrides of `beforeValidate()`, `beforeSave()`, `beforeDelete()`,
`afterSave()`, `afterFind()`, and `afterDelete()` that do not call the matching `parent::*()` method.

`beforeValidate()`, `beforeSave()`, and `beforeDelete()` must also use the parent result.

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

Mutation detection covers common Active Record writes such as `updateAttributes()`,
`updateCounters()`, `updateAllCounters()`, `insert(false)`, and `update(false)`, plus common
filesystem writes such as `rename()`, `unlink()`, `mkdir()`, and `rmdir()`.

### `yii.csrfDisabledWithoutCompensatingControl`

Reports `$this->enableCsrfValidation = false` inside controller actions and `beforeAction()`.

Use only for endpoints with an explicit compensating control such as signature verification or another boundary check.

### `yii.rawSqlConditionWithVariable`

Reports raw SQL strings built with interpolation or concatenated variables in `where()`-style
conditions, `join()` / `on()`, `from()`, `orderBy()`, and `createCommand()`.

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

### `yii.queryPerformanceSmell`

Reports Yii query expressions that execute unnecessary or inefficient SQL/data loading when a cheaper Yii API is available.

Covered patterns include `count(Model::find()->all())` / `sizeof((new Query())->column())`,
`one() === null` / `one() !== null` existence checks, query `count()` comparisons such as
`count() >= 1`, `count() !== 0`, `count() === 0`, `count() < 1`, and mirrored variants such as
`0 < count()`, plus `findOne(Yii::$app->user->id)` /
`findOne(Yii::$app->user->identity->id)` current-user lookups that should reuse
`Yii::$app->user->identity`.

### `yii.imageValidatorTooLoose`

Reports Yii `image` validator rules that do not declare any file type, size, or dimension constraint.

At least one of `extensions`, `mimeTypes`, `maxSize`, `minWidth`, or `maxWidth` should be present for `[['field'], 'image']`.

### `yii.activeDataProviderWithoutPagination`

Reports `ActiveDataProvider` and `SqlDataProvider` instances with `'pagination' => false` in web controller/action context.

### `yii.controllerBeforeActionParentResultIgnored`

Reports controller/action `beforeAction()` overrides that call `parent::beforeAction()` but ignore its boolean result.

### `yii.nPlusOneRelationInLoop`

Reports Active Record relation reads inside loops over `find()->all()` results when the relation is not loaded with `with()` or `joinWith()`.

### `yii.scenarioAssignedAfterLoad`

Reports Yii model scenario changes after `load()`, `setAttributes()`, or `$model->attributes = ...`.

Set scenario before mass assignment.

### `yii.sensitiveAttributeMarkedSafe`

Reports sensitive attributes that are mass assignable without an `on` or `except` scenario restriction.

Sensitive attributes are matched by `phpstanRules.yii2.sensitiveAttributePatterns`.
