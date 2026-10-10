# Code Review

## Executive Summary

`customers13` does use `basics13` as its foundation: it reuses shared controllers, request classes, list-query and transformer bases, audit tracking, and common migration helpers. Customer-specific models, policies, routes, queries, and presentation remain in `customers13`; this is appropriate specialization rather than duplicated generic CRUD behavior.

The package currently has important install and migration blockers. Its local Composer path points to the wrong directory, Basics13 is only a development dependency despite being required at runtime, and the customer migration calls a shared unique-index helper whose MySQL/MariaDB implementation fails for the string argument it receives. Package tests are also not runnable from the declared package test harness. The worktrees changed during this audit, so the recorded command outcomes describe the state when each command ran; checks were not rerun after the later concurrent edits.

## Detected Stack

- Package: `amaia/customers13`, requiring PHP `^8.3`; intended to run with Laravel 13 through Basics13.
- Shared package: `amaia/basics13`; package-level CRUD, requests, queries, transformers, audit behavior, and database helpers.
- Database: Basics13 helper has SQLite, PostgreSQL, and MySQL/MariaDB branches; customer feature tests configure in-memory SQLite.
- Frontend: Blade views and Laravel routes; no separate SPA runtime in this package.
- Tests: PHPUnit 12 is declared; the package test base extends Orchestra Testbench.
- Static analysis: PHPStan is configured in the package.
- Queues, Redis, Horizon, Docker deployment configuration, and CI workflows: no applicable package-owned implementation was identified.

## Checks Executed

- `bash /home/amaia/Mahaigaina/l13/packages/testing13/phpunit.sh CUS` — failed during Composer autoload, before PHPUnit tests ran. The generated vendor symlink tried to load the Basics13 helper through the nonexistent configured path.
- Package PHPStan — failed during Composer autoload for the same missing Basics13 helper; analysis did not run.
- Pint `--test` — passed for 40 PHP files at the time of the check.
- `composer validate --no-check-publish` — passed with the warning that the package `version` field is generally omitted for Packagist publication.
- No Dusk run: no package-specific browser setup was available or required for this source review.

## Critical Findings

None.

## High Findings

### PKG-001 — Composer path repository points to a nonexistent directory

Severity: HIGH  
Category: Dependencies / Package installation  
File: `composer.json`  
Line: 55  
Confidence: HIGH

Problem:

The repository URL `../packages/basics13` is resolved from the `customers13` package root, producing a path under `packages/packages/basics13`. The actual sibling package is `../basics13`.

Evidence:

The configured path does not exist, while the sibling package does. The installed `vendor/amaia/basics13` link resolves to the same nonexistent location. The attempted PHPUnit and PHPStan commands both failed while loading Basics13 from that broken link.

Impact:

Fresh dependency resolution or installation from the package root cannot reliably find its local Basics13 dependency, blocking package validation and use in this workspace.

Recommendation:

Correct the path repository URL and regenerate the lock/install state in a normal dependency-maintenance change.

### PKG-002 — Basics13 is declared only as a development dependency

Severity: HIGH  
Category: Dependencies / Runtime package contract  
File: `composer.json`  
Line: 17  
Confidence: HIGH

Problem:

`amaia/basics13` is under `require-dev`, but customer runtime code uses Basics13 classes and traits, including the customer model's audit trait and the shared controller, request, query, and transformer bases.

Evidence:

`src/Models/Customer.php` imports `Basics13\\Concerns\\TracksAuditColumns`; `src/Http/Controllers/CustomerController.php` extends a Basics13 controller; and the customer migration uses Basics13-provided global migration helpers.

Impact:

A production install of `customers13` without development dependencies does not install the package's required runtime classes.

Recommendation:

Declare Basics13 under `require` with an intentional compatible version constraint.

### DB-001 — Shared index helper fails on MySQL/MariaDB

Severity: HIGH  
Category: Database / Migration correctness  
File: `../basics13/src/Support/Database/helpers.php`  
Line: 56  
Confidence: HIGH

Problem:

The customer migration calls `addUniqueActiveNameIndex('CUM_customers')`, passing a string table name. In the MySQL/MariaDB branch, the helper calls `$table->string(...)` as though that string were a schema blueprint.

Evidence:

`src/Database/Migrations/2026_09_25_000000_create_cum_customers_table.php` passes the table name to the helper. The helper signature declares its first argument as `string $table`, then invokes a method on it in the non-SQLite/non-PostgreSQL branch.

Impact:

The customer migration fails on MySQL/MariaDB, preventing installation on those engines.

Recommendation:

Have the shared helper apply the generated column and unique index through a schema-table callback while retaining the string table-name API. Add or update driver-specific coverage.

## Medium Findings

### DB-002 — PostgreSQL partial-index SQL does not quote the mixed-case table name

Severity: MEDIUM  
Category: Database / Migration portability  
File: `../basics13/src/Support/Database/helpers.php`  
Line: 47  
Confidence: HIGH

Problem:

The PostgreSQL branch interpolates the table name into raw `CREATE UNIQUE INDEX` SQL without identifier quoting. The customer migration creates `CUM_customers`, which the schema grammar preserves as a quoted mixed-case identifier; PostgreSQL folds the helper's unquoted name to lowercase.

Evidence:

The helper explicitly routes PostgreSQL through this raw SQL branch, and the customer migration passes `CUM_customers` as the table name.

Impact:

The customer migration cannot find the case-preserved table when creating the index on PostgreSQL.

Recommendation:

Use the connection grammar to wrap table, index, and column identifiers when generating the partial-index statement.

### BUG-001 — Delete guard releases its lock before checking child projects

Severity: MEDIUM  
Category: Correctness / Data integrity  
File: `src/ServiceProvider.php`  
Line: 71  
Confidence: HIGH

Problem:

The transaction encloses only the locked customer lookup. It ends before the hook checks for projects, so a direct model delete does not hold the customer lock across the child check and soft-delete write.

Evidence:

The `DB::transaction()` call returns the locked model at line 74; the `withTrashed()->exists()` check follows at line 76. `CustomerController::destroy()` has an outer transaction and is protected, but a plain `$customer->delete()` does not. The hook's docblock claims the plain model path is protected.

Impact:

A concurrent project insert can occur after the check and before a direct model delete, leaving a project attached to a trashed customer.

Recommendation:

Either ensure every supported delete path keeps the lock through the check and soft-delete write, or narrow the hook's guarantee and require an encompassing transaction for direct deletes.

### TEST-001 — Package PHPUnit harness is not self-contained

Severity: MEDIUM  
Category: Testing / Package setup  
File: `tests/TestCase.php`  
Line: 3  
Confidence: HIGH

Problem:

The package test base is declared as `Customers13\\Tests\\TestCase`, but the test files import `Tests\\TestCase`. The package's Composer dev autoload maps only `Customers13\\Tests\\`. In addition, the base extends Orchestra Testbench, which is not declared in this package's `require-dev`; `CustomerTrashTest` also calls `User::factory()` without importing or qualifying `User`.

Evidence:

`tests/Feature/Customers/CustomerCrudTest.php` and other tests import `Tests\\TestCase`; `composer.json` maps `Customers13\\Tests\\` to `tests/`; `tests/TestCase.php` extends `Orchestra\\Testbench\\TestCase`; and `composer.json` does not require `orchestra/testbench`.

Impact:

After dependency autoload is repaired, the package test suite still cannot load its test base and has unresolved test dependencies/classes.

Recommendation:

Use the package test-base namespace consistently, declare Testbench as a development dependency, and import or qualify the `User` class in the affected test.

## Low Findings

None.

## Informational Findings

None.

## Security

No confirmed security vulnerability was identified in the reviewed customer package. The policy's broad authorization behavior is an explicit product decision unless the package is expected to enforce tenant or role boundaries.

## Bugs / Correctness

See BUG-001. The existing controller delete path wraps the operation in an outer transaction; the finding concerns the separately documented direct model-delete guarantee.

## Database

See DB-001 and DB-002. Both findings are in the shared Basics13 helper but are exercised by the customer migration. SQLite test configuration does not cover either driver branch.

## Performance

No demonstrated performance defect. A leading-wildcard search may not use a conventional index, but no workload or query-plan evidence supports an actionable performance finding.

## Architecture

The customer package follows the intended Basics13 split for common CRUD lifecycle behavior. The runtime dependency declaration and path repository configuration do not currently fulfill that package boundary.

## Testing

See TEST-001. The attempted package test command failed before tests started because of the broken Basics13 symlink path. Test files also show independent package-harness namespace and dependency issues.

## Production

The incorrect path repository and dev-only Basics13 dependency can prevent a working production install. The shared migration helper also blocks MySQL/MariaDB customer migrations.

## Maintainability

No confirmed duplicated CRUD, request, query-base, transformer, or audit implementation was found in the current customer package. Customer-specific query and presentation behavior is appropriately specialized.

## Rejected Findings

- Search predicate consolidation was rejected as optional refactoring: behavior currently matches the shared search pattern, and no defect was demonstrated.
- Duplicate route registration was rejected as a runtime issue: the duplicate provider method is unused; the route file is the active source.
- Leading-wildcard search performance was rejected without workload or query-plan evidence.
- A stale Basics13 helper-class call was rejected after the live customer migration changed to use the global helper functions.
- Global helper redeclaration was rejected: the customer helper file has been removed from the current tree.

## Action Plan

1. **P1:** Fix the local Basics13 path repository and declare Basics13 as a runtime dependency.
2. **P1:** Correct the shared unique-index helper for MySQL/MariaDB and quote PostgreSQL identifiers; add driver-specific migration tests.
3. **P2:** Repair the package PHPUnit namespace and Testbench dependency, then resolve the missing `User` import.
4. **P2:** Make the direct model-delete transaction guarantee consistent with the child check and soft-delete write.