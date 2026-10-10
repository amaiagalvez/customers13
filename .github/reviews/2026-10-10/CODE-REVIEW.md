# Code Review

## Executive Summary

`customers13` does use `basics13` as its foundation: it reuses shared controllers, request classes, list-query and transformer bases, audit tracking, and common migration helpers. Customer-specific models, policies, routes, queries, and presentation remain in `customers13`; this is appropriate specialization rather than duplicated generic CRUD behavior.

The package uses Basics13 for shared CRUD behavior rather than duplicating it. The three actionable findings from the review have now been corrected: Basics13 is a runtime dependency, the partial-index SQL quotes identifiers, and direct customer deletion keeps the guard and soft delete in one transaction. The package test harness is also repaired. Current automated checks pass; the earlier `runAll.sh cus` failures with `tempnam()` warnings did not reproduce in the final no-coverage suite.

## Detected Stack

- Package: `amaia/customers13`, requiring PHP `^8.3`; intended to run with Laravel 13 through Basics13.
- Shared package: `amaia/basics13`; package-level CRUD, requests, queries, transformers, audit behavior, and database helpers.
- Database: Basics13 helper has SQLite, PostgreSQL, and MySQL/MariaDB branches; customer feature tests configure in-memory SQLite.
- Frontend: Blade views and Laravel routes; no separate SPA runtime in this package.
- Tests: PHPUnit 12 is declared; the package test base extends Orchestra Testbench.
- Static analysis: PHPStan is configured in the package.
- Queues, Redis, Horizon, Docker deployment configuration, and CI workflows: no applicable package-owned implementation was identified.

## Checks Executed

- Customer PHPUnit suite without coverage — passed: 25 tests, 102 assertions.
- Customer PHPUnit suite with Xdebug coverage — passed: 25 tests, 102 assertions; coverage output was written inside the disposable container.
- Basics13 database-helper unit tests — passed: 9 tests, 16 assertions.
- PHPStan — passed for both `customers13` and `basics13` with no errors.
- Pint `--test` — passed for the two changed PHP files in each package.
- Composer `update amaia/basics13 --no-install --no-interaction` — completed without changing other package versions; `composer validate --no-check-publish` passed, with only the existing version-field warning.
- The previous `runAll.sh cus` execution reported 4 failures with `tempnam()` warnings; these did not reproduce in either final complete PHPUnit run. The cause of that earlier result was not established.
- Dusk and live PostgreSQL/MariaDB migration runs were not performed; the focused migration-helper test ran on SQLite.
- No Dusk run: no package-specific browser setup was available or required for this source review.

## Critical Findings

None.

## High Findings

### PKG-002 — Basics13 is declared only as a development dependency

Severity: HIGH  
Category: Dependencies / Runtime package contract  
File: `composer.json`
Line: 14
Confidence: HIGH
Status: FIXED

Problem:

`amaia/basics13` is under `require-dev`, but customer runtime code uses Basics13 classes and traits, including the customer model's audit trait and the shared controller, request, query, and transformer bases.

Evidence:

`src/Models/Customer.php` imports `Basics13\\Concerns\\TracksAuditColumns`; `src/Http/Controllers/CustomerController.php` extends a Basics13 controller; and the customer migration uses Basics13-provided global migration helpers.

Impact:

A production install of `customers13` without development dependencies does not install the package's required runtime classes.

Recommendation:

Basics13 is now declared under `require` with the `^1.0` constraint, and the lockfile was refreshed.

## Medium Findings

### DB-002 — PostgreSQL partial-index SQL does not quote the mixed-case table name

Severity: MEDIUM  
Category: Database / Migration portability  
File: `../basics13/src/Support/Database/helpers.php`
Line: 54
Confidence: HIGH
Status: FIXED

Problem:

The PostgreSQL branch interpolates the table name into raw `CREATE UNIQUE INDEX` SQL without identifier quoting. The customer migration creates `CUM_customers`, which the schema grammar preserves as a quoted mixed-case identifier; PostgreSQL folds the helper's unquoted name to lowercase.

Evidence:

The helper explicitly routes PostgreSQL through this raw SQL branch, and the customer migration passes `CUM_customers` as the table name.

Impact:

The customer migration cannot find the case-preserved table when creating the index on PostgreSQL.

Recommendation:

The helper now uses Laravel's active query grammar to wrap the index, table, columns, and `deleted_at` identifiers. A focused SQL assertion verifies quoting with a mixed-case table name.

### BUG-001 — Delete guard releases its lock before checking child projects

Severity: MEDIUM  
Category: Correctness / Data integrity  
File: `src/Models/Customer.php`
Line: 68
Confidence: HIGH
Status: FIXED

Problem:

The transaction encloses only the locked customer lookup. It ends before the hook checks for projects, so a direct model delete does not hold the customer lock across the child check and soft-delete write.

Evidence:

The `DB::transaction()` call returns the locked model at line 74; the `withTrashed()->exists()` check follows at line 76. `CustomerController::destroy()` has an outer transaction and is protected, but a plain `$customer->delete()` does not. The hook's docblock claims the plain model path is protected.

Impact:

A concurrent project insert can occur after the check and before a direct model delete, leaving a project attached to a trashed customer.

Recommendation:

`Customer::delete()` now wraps the parent delete operation in a transaction on the model's connection. A feature test verifies the guard's lock query runs within that outer transaction.

## Low Findings

None.

## Informational Findings

None.

## Security

No confirmed security vulnerability was identified in the reviewed customer package. The policy's broad authorization behavior is an explicit product decision unless the package is expected to enforce tenant or role boundaries.

## Bugs / Correctness

BUG-001 is fixed. Both controller and direct model deletion now keep the guard and soft-delete operation inside an encompassing transaction.

## Database

DB-002 is fixed by grammar-based identifier wrapping. The MySQL/MariaDB branch uses `Schema::table()` with a blueprint callback. Runtime migration checks against PostgreSQL and MariaDB were not available; the focused helper test passed on SQLite.

## Performance

No demonstrated performance defect. A leading-wildcard search may not use a conventional index, but no workload or query-plan evidence supports an actionable performance finding.

## Architecture

The customer package follows the intended Basics13 split for common CRUD lifecycle behavior. The runtime dependency declaration and path repository configuration do not currently fulfill that package boundary.

## Testing

The test base namespace, Testbench development dependency, and fixture model autoloading are corrected. The final complete package suite passed. The earlier `tempnam()` warnings and associated failures did not reproduce without coverage.

## Production

The runtime dependency declaration and PostgreSQL migration issue are fixed. The configured path repository is compatible with the project's Docker runner, which mounts the package collection at `/packages`.

## Maintainability

No confirmed duplicated CRUD, request, query-base, transformer, or audit implementation was found in the current customer package. Customer-specific query and presentation behavior is appropriately specialized.

## Rejected Findings

- Search predicate consolidation was rejected as optional refactoring: behavior currently matches the shared search pattern, and no defect was demonstrated.
- Duplicate route registration was rejected as a runtime issue: the duplicate provider method is unused; the route file is the active source.
- Leading-wildcard search performance was rejected without workload or query-plan evidence.
- The Composer path was rejected as a current defect: the supported Docker package runner mounts the package collection at `/packages`, which makes the configured relative path resolve as intended.
- The MySQL/MariaDB index-helper failure was removed after the shared helper was changed to use `Schema::table()` and a `Blueprint` callback.
- The earlier test-base namespace, missing Testbench dependency, and missing fixture model mapping were removed after those harness changes were made.
- The earlier `tempnam()` test failures were not reproducible in the final complete no-coverage suite; no product defect was confirmed from that output.

## Action Plan

No open code findings remain from this review. The remaining verification gap is running the migration against live PostgreSQL and MariaDB instances.