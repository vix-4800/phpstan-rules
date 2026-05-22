# PHPStan Rules

PHPStan rules package with framework-specific rule sets.

[![Tests](https://github.com/vix-4800/phpstan-rules/actions/workflows/tests.yml/badge.svg)](https://github.com/vix-4800/phpstan-rules/actions/workflows/tests.yml)
[![PHPStan](https://github.com/vix-4800/phpstan-rules/actions/workflows/phpstan.yml/badge.svg)](https://github.com/vix-4800/phpstan-rules/actions/workflows/phpstan.yml)
[![PHP Version](https://img.shields.io/badge/php-%5E8.4-blue)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

- [PHPStan Rules](#phpstan-rules)
  - [Setup](#setup)
  - [Versioning](#versioning)
  - [Rules](#rules)

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

Enable all currently available rules:

```neon
parameters:
    vixPhpstan:
        php:
            rules:
                all: true
        yii2:
            rules:
                all: true
```

Or enable selected rules:

```neon
parameters:
    vixPhpstan:
        php:
            rules:
                remoteFileGetContents: true
                disabledSslVerification: true
        yii2:
            rules:
                missingAccessRule: true
                missingVerbFilterRule: true
                queryOneWithoutLimit: true
```

Current config from `extension.neon`:

```neon
parameters:
    vixPhpstan:
        php:
            rules:
                all: false
                disabledSslVerification: false
                httpClientWithoutTimeout: false
                remoteFileGetContents: false
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
                webControllerOnlyActions: false
            allowedSaveFalseNamespaces: []
            sensitiveAttributePatterns:
                - '~^(id|user_id|created_at|updated_at|created_by|updated_by|role|status|...|is_admin)$~i'
```

Override example:

```neon
parameters:
    vixPhpstan:
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

## Rules

Detailed rule descriptions, before/after examples, and rule-specific parameters are documented in [docs/RULES.md](docs/RULES.md).
