# Health v3 Migration Strategy

## Purpose

This document defines the approved database migration strategy for the rewritten Health application.

The application keeps the Nextcloud app ID `health` and starts its rewritten release line at application version `3.0.0`. The public Health API remains API v2; the application release version and the API version are intentionally independent.

This strategy covers safe schema installation and coexistence with Health 2.x. It does not define or implement legacy data import.

## Verified legacy baseline

The read-only `health-legacy` reference repository was inspected rather than relying on assumed table or migration names.

Its application metadata declares:

- app ID: `health`
- application version: `2.2.2`

The repository contains these migration classes, in identifier order:

1. `Version0023Date20200903130000`
2. `Version0123Date20201028200000`
3. `Version0140Date20201117200000`
4. `Version0142Date20201104200000`
5. `Version0230Date20210107000000`
6. `Version0230Date20210110000000`
7. `Version0300Date20210119000000`
8. `Version0310Date20210130000000`
9. `Version1100Date20210414000000`
10. `Version1200Date20210429000000`
11. `Version1500Date20220416000000`
12. `Version1600Date20220715000000`

The highest migration class/version identifier shipped by the inspected legacy application is:

```text
OCA\Health\Migration\Version1600Date20220715000000
```

The legacy migration chain creates or refers to the following actual table identifiers:

- `health_persons`
- `health_weightdata`
- `health_feelingdata`
- `health_measurementdata`
- `health_sleepdata`
- `health_smokingdata`
- `health_activitiesdata`
- `health_medicationplans`
- `health_medicationdata`
- `health_persons_acl`
- `health_ces_contexts`
- `health_ces_entities`

`health_ces_contexts` and `health_ces_entities` are historically transient in the inspected chain: `Version0140Date20201117200000` creates them and `Version1100Date20210414000000` drops them. The other listed table identifiers are created or used without a later table drop in the inspected migrations. Real installations may still differ because of failed, interrupted, or historically partial upgrades, so Health v3 must preserve the schema it encounters rather than trying to normalize legacy tables.

The inspected chain also contains two distinct migrations with the `Version0230` prefix, differentiated by their date suffixes. In addition, the date suffix of `Version0142Date20201104200000` predates the date suffix of `Version0140Date20201117200000`. A new migration must therefore be checked against every complete legacy class identifier and must not infer uniqueness or ordering from only the numeric prefix or only the date suffix.

The planned Health v3 table identifiers in `DATA_MODEL.md` do not collide with the literal legacy table identifiers found in the inspected migration directory. This does not remove the requirement to test both fresh installation and real upgrade paths.

## Approved migration rules

### Application identity and version

- The app ID remains `health` so Nextcloud treats Health v3 as an upgrade of the installed Health application rather than as an unrelated app.
- The rewritten application starts at version `3.0.0` because the legacy application already reached version `2.2.2`.
- Existing Health 2.x installations may contain legacy tables and personal Health data.

### Non-destructive coexistence

- During the MVP, Health v3 must not delete, rename, reinterpret, truncate, or destructively alter any legacy table, column, index, constraint, or row.
- Health v3 must not claim a legacy column has new metric semantics merely because its value could be represented by the new generic metric model.
- New Health v3 tables must coexist with any legacy tables that are present.
- A fresh Health v3 installation must create and use the v3 schema without requiring any legacy table or migration state.
- An upgrade from Health `2.2.2` must install the v3 schema while preserving all legacy schema and data exactly as encountered.
- Legacy rows must not be copied automatically into the new generic metric model during installation or upgrade.
- Legacy data migration is a separate, explicitly deferred feature.
- Removing legacy tables requires a separate future architectural decision and is not part of the MVP.

### Migration mechanism and identity

- Database changes must use official Nextcloud migration mechanisms in `lib/Migration/`, including the supported schema migration interfaces and schema wrapper. Schema-changing SQL must not run during normal requests.
- The first Health v3 migration must have a complete class/version identifier that Nextcloud 33 recognizes as newer than every migration shipped by the legacy application.
- In particular, it must be newer than `Version1600Date20220715000000`.
- The exact first v3 migration identifier must be selected when migration implementation begins, after rechecking the complete legacy migration directory and validating ordering with the Nextcloud 33 migration tooling.
- No legacy migration class/version identifier may be reused, including either of the two `Version0230...` identifiers.
- Once a v3 migration has shipped, it must be treated as immutable. Corrections must use a new, later migration.
- V3 migrations must not depend on legacy tables being present. They must support both a database with the completed legacy chain and a database with no Health schema.

### Upgrade behavior

The schema upgrade and any future data import are separate operations:

1. Normal Nextcloud installation or upgrade applies the new Health v3 schema migration.
2. Legacy tables and rows remain untouched.
3. Health v3 operates only on its v3 schema.
4. No legacy values appear as v3 entries unless a separately approved future import is explicitly run.

Normal migration tracking must make a completed schema upgrade safe to encounter again. Re-running ordinary Nextcloud upgrade commands must not duplicate v3 tables, replay data transformations, or modify legacy data.

Disable, re-enable, and ordinary uninstall lifecycle paths must not include Health cleanup code that unexpectedly deletes personal data. The MVP must not add an uninstall hook or cleanup path that drops either legacy or v3 Health tables. Any future permanent-data-removal behavior requires an explicit, separately reviewed user action and architectural decision.

## Migration acceptance scenarios

### A. Fresh installation of Health 3.0.0

Given a supported Nextcloud 33 installation with no Health migration history and no Health tables:

- installing and enabling Health `3.0.0` succeeds;
- the complete v3 schema is created through official Nextcloud migration mechanisms;
- no legacy table is required or created merely for compatibility;
- Health can use the empty v3 schema; and
- running normal upgrade commands again makes no schema or data change.

### B. Upgrade from Health 2.2.2 with existing data

Given Health `2.2.2` with populated legacy tables:

- upgrading to Health `3.0.0` succeeds;
- the v3 schema is installed;
- every pre-upgrade legacy table, column, index, constraint, and row remains unchanged by Health v3;
- no legacy row is automatically copied into a v3 table;
- v3 starts with no imported legacy entries; and
- checks of legacy row counts and representative records before and after the upgrade match.

The upgrade test fixture must contain representative data from the actual legacy schema, including relationships where present, rather than a guessed approximation.

### C. Upgrade from Health 2.2.2 with empty legacy tables

Given Health `2.2.2` with its legacy schema present but with no personal Health rows:

- upgrading to Health `3.0.0` succeeds;
- the v3 schema is installed;
- empty legacy tables remain present and unchanged; and
- no import markers, placeholder entries, or synthetic v3 data are created.

### D. Re-running normal Nextcloud upgrade commands

Given a successful Health `3.0.0` installation or upgrade:

- re-running the normal Nextcloud upgrade and migration status commands succeeds;
- the first v3 migration is not applied a second time;
- no duplicate tables, indexes, constraints, or rows are created;
- no legacy object is altered; and
- the migration state remains complete and consistent.

This scenario must be checked after both scenario A and scenario B.

### E. Uninstall, disable, and re-enable

Given either legacy data, v3 data, or both:

- disabling Health does not delete or alter personal data;
- re-enabling Health restores access without rebuilding or resetting the schema;
- an ordinary uninstall path does not invoke Health-owned destructive schema or data cleanup; and
- reinstalling or re-enabling does not reinterpret legacy data or automatically import it.

Any Nextcloud UI or command that offers an explicit permanent data purge must remain outside this MVP strategy and requires separate design, confirmation, and tests.

## Deferred legacy data import

Legacy data import will be designed and implemented as a later feature, independently of schema installation. At a high level, that work may:

1. inventory legacy records per owning Nextcloud user without changing their source rows;
2. define an explicit, reviewed mapping from each supported legacy field to a Health v3 module and atomic metric identifier;
3. normalize timestamps, units, notes, and other context only where the mapping is unambiguous and documented;
4. validate ownership and values before writing any v3 entry;
5. process bounded transactional batches and record sufficient progress to resume safely;
6. use stable source identity or equivalent idempotency rules so restarting cannot create duplicate v3 entries;
7. reconcile source, imported, skipped, and failed counts without logging sensitive Health values;
8. leave unsupported or ambiguous legacy records untouched and report them without destructive fallback; and
9. preserve legacy tables as the source of record until a separately approved retirement decision exists.

The later design must be restartable or otherwise guarantee that interruption cannot leave a partially destructive migration. It must define retry, deduplication, verification, and rollback behavior before implementation. This document does not select an importer API, add import-state storage, define field-level mappings, or authorize any data copy.

## Out of scope for the MVP

- importing or transforming legacy Health data;
- deleting, renaming, or consolidating legacy tables;
- changing the meaning of legacy values;
- adding compatibility views over legacy tables;
- automatically exposing legacy values through Health API v2;
- implementing data-purge behavior; and
- choosing or implementing the first v3 migration class.

## Profile extension migration

`Version3001Date20260820230000` is an additive Health v3 migration. It adds nullable `date_of_birth` and `growth_reference_sex` columns to the existing v3 `health_user_settings` table without changing legacy Health tables or existing rows. Both fields are owner-scoped configuration and support only the documented profile API values.

## Goals migration

`Version3003Date20260824120000` is additive and creates `health_goals`, `health_goal_revisions`, and `health_goal_reminder_state` only when absent. It does not change legacy tables, journal rows, daily values, measurements, user settings, or existing Health data. The unique goal identity is `(user_id, target_key, period)`; revision and reminder-state unique indexes preserve historical period semantics and notification deduplication. The migration is schema-only and creates no goals, progress rows, notifications, or Health values.

## Saved Statistics views migration

`Version3004Date20260902230000` is additive and creates `health_statistics_views` only when absent. It stores private reusable Statistics configuration, never derived statistics or Health values. It does not change legacy tables, journal rows, goals, settings, measurements, daily values, or existing Health data.

## Offline replay identity migration

`Version3005Date20260905190000` is additive and adds nullable `client_operation_id` columns plus owner-scoped unique replay indexes to the v3 entry and measurement tables. Existing rows remain null and unchanged. The migration stores no new Health values, creates no PWA-specific server records, and does not alter any released migration.
