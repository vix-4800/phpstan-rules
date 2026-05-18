# Rule Reference

This page documents the rules currently shipped in this package.

## Table of Contents

- [Rule Reference](#rule-reference)
  - [Table of Contents](#table-of-contents)
  - [Yii2](#yii2)
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

## Yii2

### `yii.missingAccessRule`

Detects public controller actions that are not covered by `yii\filters\AccessControl`.

Before

```php
public function behaviors(): array
{
    return [];
}

public function actionDelete(int $id): Response
{
    return $this->redirect(['view', 'id' => $id]);
}
```

After

```php
public function behaviors(): array
{
    return [
        'access' => [
            'class' => AccessControl::class,
            'only' => ['delete'],
            'rules' => [
                [
                    'allow' => true,
                    'roles' => ['@'],
                    'actions' => ['delete'],
                ],
            ],
        ],
    ];
}
```

### `yii.missingVerbFilterRule`

Detects controller actions that are not constrained by `yii\filters\VerbFilter`.

Before

```php
public function behaviors(): array
{
    return [];
}
```

After

```php
public function behaviors(): array
{
    return [
        'verbs' => [
            'class' => VerbFilter::class,
            'actions' => [
                'delete' => ['POST'],
            ],
        ],
    ];
}
```

### `yii.missingAjaxFilterRule`

Detects JSON-style controller actions that look like AJAX endpoints but are not protected by `yii\filters\AjaxFilter`.

Before

```php
public function actionPreview(): Response
{
    return $this->asJson(['ok' => true]);
}
```

After

```php
public function behaviors(): array
{
    return [
        'ajax' => [
            'class' => AjaxFilter::class,
            'only' => ['preview'],
        ],
    ];
}
```

### `yii.unknownActionInBehavior`

Detects behavior action names that do not correspond to a real `action*()` method or an external action key from `actions()`.

Before

```php
'only' => ['index', 'missing-action']
```

After

```php
'only' => ['index']
```

### `yii.massSelectionWithoutLimit`

Detects `find()->all()` chains that load an unbounded Active Record result set.

Before

```php
$posts = Post::find()->where(['status' => Post::STATUS_ACTIVE])->all();
```

After

```php
$posts = Post::find()
    ->where(['status' => Post::STATUS_ACTIVE])
    ->limit(100)
    ->all();
```

### `yii.saveFalseWithoutReason`

Detects `save(false)` calls that bypass validation without an explicit justification.

Before

```php
$user->save(false);
```

After

```php
$user->save();
```

Parameters

```neon
parameters:
    phpstanRules:
        yii2:
            allowedSaveFalseNamespaces:
                - app\migrations
```

Namespaces containing `migrations`, `tests`, `seeders`, or `seeds` are already allowed by default.

### `yii.fileValidatorTooLoose`

Detects `file` validators that accept uploads without any file type restriction.

Before

```php
[['attachment'], 'file']
```

After

```php
[['attachment'], 'file', 'extensions' => ['pdf'], 'mimeTypes' => ['application/pdf']]
```

### `yii.lifecycleParentCall`

Detects Active Record lifecycle overrides that skip the matching `parent::*()` call, or ignore its return value for `before*()` hooks.

Before

```php
public function beforeSave($insert): bool
{
    $this->updated_at = time();

    return true;
}
```

After

```php
public function beforeSave($insert): bool
{
    if (!parent::beforeSave($insert)) {
        return false;
    }

    $this->updated_at = time();

    return true;
}
```

### `yii.componentInitParentCall`

Detects `init()` overrides in Yii components that skip `parent::init()`.

Before

```php
public function init(): void
{
    $this->client = new Client();
}
```

After

```php
public function init(): void
{
    parent::init();
    $this->client = new Client();
}
```

### `yii.lifecycleSelfSave`

Detects `$this->save()`, `$this->update()`, and `$this->delete()` calls from inside Active Record lifecycle hooks.

Before

```php
public function afterSave($insert, $changedAttributes): void
{
    $this->status = self::STATUS_SYNCED;
    $this->save(false);
}
```

After

```php
public function afterSave($insert, $changedAttributes): void
{
    Yii::$app->queue->push(new SyncStatusJob(['id' => $this->id]));
}
```

### `yii.publicAllowWithoutConstraint`

Detects `AccessControl` rules that allow access without any practical constraint.

Before

```php
[
    'allow' => true,
]
```

After

```php
[
    'allow' => true,
    'roles' => ['@'],
    'actions' => ['index'],
]
```

### `yii.mutatingActionAllowsGet`

Detects mutating controller actions whose `VerbFilter` still allows `GET` or `HEAD`.

Before

```php
'delete' => ['GET', 'POST']
```

After

```php
'delete' => ['POST']
```

### `yii.csrfDisabledWithoutCompensatingControl`

Detects controller code that disables CSRF validation without an obvious replacement control.

Before

```php
public function beforeAction($action): bool
{
    $this->enableCsrfValidation = false;

    return parent::beforeAction($action);
}
```

After

```php
public function beforeAction($action): bool
{
    if (!$this->verifyWebhookSignature()) {
        return false;
    }

    return parent::beforeAction($action);
}
```

### `yii.rawSqlConditionWithVariable`

Detects raw SQL fragments built with interpolation or concatenation instead of bound parameters.

Before

```php
$query->where("id = $id");
```

After

```php
$query->where(['id' => $id]);
```

### `yii.deleteAllOrUpdateAllWithoutWhere`

Detects `deleteAll()` and `updateAll()` calls that have no meaningful condition.

Before

```php
User::deleteAll();
```

After

```php
User::deleteAll(['status' => User::STATUS_DISABLED]);
```

### `yii.transactionWithoutRollbackHandling`

Detects transactions that can throw but never roll back from a `catch` block.

Before

```php
$transaction = Yii::$app->db->beginTransaction();
$model->save();
$transaction->commit();
```

After

```php
$transaction = Yii::$app->db->beginTransaction();

try {
    $model->save();
    $transaction->commit();
} catch (\Throwable $exception) {
    $transaction->rollBack();
    throw $exception;
}
```

### `yii.queryOneWithoutLimit`

Detects `one()` query chains that do not make the `LIMIT 1` intent explicit.

Before

```php
$post = Post::find()->where(['slug' => $slug])->one();
```

After

```php
$post = Post::find()->where(['slug' => $slug])->limit(1)->one();
```

### `yii.redirectReferrerWithoutFallback`

Detects redirects to the request referrer when no fallback route is provided.

Before

```php
return $this->redirect(Yii::$app->request->referrer);
```

After

```php
return $this->redirect(Yii::$app->request->referrer ?: ['index']);
```

### `yii.nativeHeaderInController`

Detects native `header()` calls inside Yii controllers.

Before

```php
header('Location: /login');
return;
```

After

```php
return $this->redirect(['site/login']);
```

### `yii.mixedResponseTypesInAction`

Detects controller actions that return JSON in one branch and HTML or redirects in another branch.

Before

```php
if (Yii::$app->request->isAjax) {
    return $this->asJson(['ok' => true]);
}

return $this->redirect(['index']);
```

After

```php
return $this->asJson([
    'ok' => true,
    'redirect' => Url::to(['index']),
]);
```

### `yii.unboundedQueryResult`

Detects `all()` and `column()` query chains that fetch an unbounded result set.

Before

```php
$ids = (new Query())->from('post')->select('id')->column();
```

After

```php
$ids = (new Query())
    ->from('post')
    ->select('id')
    ->limit(500)
    ->column();
```

### `yii.queryPerformanceSmell`

Detects inefficient query patterns when Yii already provides a cheaper equivalent.

Before

```php
$count = count(Post::find()->all());
```

After

```php
$count = (int) Post::find()->count();
```

### `yii.imageValidatorTooLoose`

Detects `image` validators that do not specify any type, size, or dimension constraints.

Before

```php
[['avatar'], 'image']
```

After

```php
[['avatar'], 'image', 'extensions' => ['png', 'jpg'], 'maxSize' => 1024 * 1024]
```

### `yii.activeDataProviderWithoutPagination`

Detects web controller code that disables pagination on `ActiveDataProvider` or `SqlDataProvider`.

Before

```php
return new ActiveDataProvider([
    'query' => Post::find(),
    'pagination' => false,
]);
```

After

```php
return new ActiveDataProvider([
    'query' => Post::find(),
    'pagination' => [
        'pageSize' => 50,
    ],
]);
```

### `yii.controllerBeforeActionParentResultIgnored`

Detects `beforeAction()` overrides that call `parent::beforeAction()` but keep running even when it returns `false`.

Before

```php
public function beforeAction($action): bool
{
    parent::beforeAction($action);

    return true;
}
```

After

```php
public function beforeAction($action): bool
{
    if (!parent::beforeAction($action)) {
        return false;
    }

    return true;
}
```

### `yii.nPlusOneRelationInLoop`

Detects relation access inside loops over `find()->all()` results when the relation was not eagerly loaded.

Before

```php
foreach (Post::find()->all() as $post) {
    echo $post->author->email;
}
```

After

```php
foreach (Post::find()->with('author')->all() as $post) {
    echo $post->author->email;
}
```

### `yii.scenarioAssignedAfterLoad`

Detects scenario changes that happen after mass assignment.

Before

```php
$model->load(Yii::$app->request->post());
$model->scenario = User::SCENARIO_CREATE;
```

After

```php
$model->scenario = User::SCENARIO_CREATE;
$model->load(Yii::$app->request->post());
```

### `yii.sensitiveAttributeMarkedSafe`

Detects sensitive attributes that are marked safe for mass assignment without scenario restrictions.

Before

```php
[['role', 'is_admin'], 'safe']
```

After

```php
[['display_name'], 'safe'],
[['role'], 'safe', 'on' => 'admin-update']
```

Parameters

```neon
parameters:
    phpstanRules:
        yii2:
            sensitiveAttributePatterns:
                - '~^(role|status|is_admin|access_token)$~i'
```

The default configuration already includes common identifiers, audit fields, role-like fields, and authentication secrets.
