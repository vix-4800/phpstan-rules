# AGENTS.md

## Project Scope

This repository is a PHPStan extension package for generic PHP and Yii2-specific static analysis rules.

Primary code areas:

- `src/Rules/` contains rule implementations.
- `src/Support/` contains shared analyzers, factories, and framework helpers.
- `tests/Rules/` contains PHPUnit rule tests.
- `docs/RULES.md` documents shipped rules and examples.
- `rules.neon` registers services for each rule.
- `extension.neon` defines exposed config schema and default flags.

## Working Rules

- Keep changes narrow and local to the rule or helper being touched.
- Follow existing PHP style: `declare(strict_types=1);`, final classes, typed properties/constants, and explicit return types.
- Reuse `NodeHelpers` and existing support classes before adding new helpers.
- Prefer fixing root-cause detection logic over adding special cases in tests.
- Do not refactor unrelated rules or support classes while fixing one rule.

## Adding Or Changing A Rule

When adding a new rule, update all relevant surfaces together:

1. Add the rule class in `src/Rules/`.
2. Add or update the matching PHPUnit test in `tests/Rules/`.
3. Add or update test fixtures under `tests/Rules/data/` when needed.
4. Register the service in `rules.neon` using the existing configurable-rule pattern.
5. Add the config key and default flag in `extension.neon`.
6. Document the rule in `docs/RULES.md`.
7. Update `README.md` if the public setup or rule list changes.

Match existing conventions:

- Rule classes implement `PHPStan\Rules\Rule` with precise node types.
- Rule errors use `RuleErrorBuilder` with stable `identifier()` values prefixed with `vix.`.
- Tests extend `PHPStan\Testing\RuleTestCase` and assert exact messages and line numbers.
- New config toggles should mirror existing naming in both NEON files.

## Validation

Run the narrowest useful check first after changes:

- Targeted test: `vendor/bin/phpunit tests/Rules/<RuleName>Test.php`
- Full test suite: `composer test`
- Static analysis for package code: `composer static-analysis`

Prefer targeted PHPUnit coverage for single-rule changes, then run broader validation if shared support code or config wiring changed.

## Documentation Expectations

- Keep examples in `docs/RULES.md` minimal and concrete.
- When behavior changes, update docs in the same change.
- Keep README examples aligned with currently exposed config keys.

## Avoid

- Broad formatting-only edits.
- Renaming public config keys unless explicitly required.
- Changing identifiers or error messages without updating tests and docs.
- Adding new dependencies unless the task requires them.
