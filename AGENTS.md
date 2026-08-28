# Health v2 – Agent Instructions

## 1. Purpose

This repository contains **Health v2**, a classic Nextcloud PHP application with a Vue 3 frontend.

Health is developed API-first and privacy-first.

AI coding agents are expected to implement substantial parts of this application autonomously, but they must follow the architecture and product specifications in this repository.

Do not invent architecture while implementing features.

---

# 2. Read Before Working

Before modifying code, read these files in this order:

1. `PRODUCT.md`
2. `MVP.md`
3. `ARCHITECTURE.md`
4. `DATA_MODEL.md`
5. `API.md`
6. `SECURITY.md`
7. `UI.md`

These documents are authoritative.

If implementation code conflicts with these documents, do not silently choose one.

Determine whether:

* the implementation is wrong, or
* the specification requires an explicit update.

Architectural decisions must not be changed accidentally during feature work.

---

# 3. Product Summary

Health is a private health and wellbeing journal for Nextcloud.

The MVP focuses on:

* configurable health tracking
* quick journal entries
* check-in
* check-out
* goals
* reminders
* weekly and monthly overview
* descriptive statistics

Health does not provide:

* medical diagnosis
* AI interpretation
* employer monitoring
* administrator health dashboards
* social health features

---

# 4. Target Platform

Minimum supported Nextcloud version:

```text
Nextcloud 33
```

Use APIs officially supported by Nextcloud 33.

Do not use private Nextcloud internals.

Do not rely on undocumented behavior.

---

# 5. Application Architecture

Health is a classic Nextcloud application.

Backend:

```text
PHP
Nextcloud App Framework
OCS
Nextcloud database APIs
```

Frontend:

```text
Vue 3
TypeScript
@nextcloud/vue
```

Charts:

```text
Chart.js
```

Do not introduce AppAPI or ExApps.

---

# 6. API First

The public Health API is the central application interface.

The Vue frontend is a client of:

```text
Health OCS API v2
```

Base API:

```text
/ocs/v2.php/apps/health/api/v2/
```

Do not create a separate private frontend API.

Persistent frontend operations must use the public Health API.

---

# 7. API Implementation

Public API controllers must use official Nextcloud OCS mechanisms.

Use:

* `OCSController`
* `DataResponse`
* official OCS exceptions
* appropriate Nextcloud route attributes
* explicit PHP types
* OpenAPI-compatible documentation

Every public endpoint must be extractable using the official Nextcloud OpenAPI tooling.

---

# 8. API Documentation

OpenAPI documentation is part of the feature.

Do not consider an API endpoint complete until:

* parameters are typed
* request fields are documented
* responses are documented
* response definitions are explicit
* status codes are documented
* OpenAPI extraction succeeds

Reusable response types should be defined centrally where appropriate.

Preferred location:

```text
lib/ResponseDefinitions.php
```

---

# 9. Backend Layers

Use the following architecture:

```text
OCS Controller
      ↓
Service
      ↓
Mapper / Repository
      ↓
Database
```

## Controllers

Controllers:

* receive requests
* validate request structure
* invoke services
* return OCS responses

Controllers must remain thin.

Do not put business logic in controllers.

Do not put SQL in controllers.

---

## Services

Services contain domain and application logic.

Examples:

* metric validation
* journal entry creation
* goal evaluation
* statistics
* summaries

Services must not depend on frontend behavior.

---

## Mappers

Mappers are responsible for persistence.

Use official Nextcloud database APIs.

Do not put domain rules in mappers.

---

# 10. Modules and Metrics

Health distinguishes between modules and atomic metrics.

Examples:

```text
Module: sleep

Metrics:
- sleep_duration
- sleep_recovery
```

Metric definitions are application definitions.

Metric identifiers are stable public API identifiers.

Do not create separate database tables for each metric.

Do not store arbitrary metric payloads as generic JSON.

Read `DATA_MODEL.md` before modifying metric behavior.

---

# 11. User Ownership

All personal Health data belongs to the authenticated Nextcloud user.

Never trust a client-provided user ID for authorization.

Every personal resource query must be scoped to the authenticated user.

This includes:

* entries
* goals
* reminders
* configuration
* summaries
* statistics

---

# 12. Security

Read `SECURITY.md` before changing:

* authentication
* controllers
* API routes
* persistence
* logging
* notifications
* sharing
* CORS
* CSRF behavior

Do not add to Health data API endpoints:

```php
#[PublicPage]
#[NoCSRFRequired]
#[CORS]
#[NoTwoFactorRequired]
```

without an explicit architectural decision.

Regular authenticated users may use appropriate:

```php
#[NoAdminRequired]
```

behavior.

---

# 13. Sensitive Data

Never log Health values.

Do not log:

* journal entries
* stress values
* mood
* weight
* sleep values
* notes
* API request bodies
* API response bodies containing Health information

Do not add telemetry containing Health data.

---

# 14. Frontend

Use:

```text
Vue 3
TypeScript
@nextcloud/vue
```

The interface must follow `UI.md`.

Do not introduce another general UI framework.

Forbidden examples:

```text
Vuetify
Bootstrap UI
Quasar
PrimeVue
Material UI
Tailwind UI component systems
```

---

# 15. Nextcloud Vue Components

Use official `@nextcloud/vue` components whenever an appropriate component exists.

Do not recreate standard Nextcloud:

* buttons
* dialogs
* inputs
* menus
* toggles
* selects
* navigation
* loading states
* empty states

Custom components are appropriate only for Health-specific behavior.

Examples:

```text
MetricInput
QuickEntry
HealthHeatmap
MetricChart
GoalStatus
CheckinForm
```

Before using a component API, verify that it exists in the installed `@nextcloud/vue` version.

Do not invent component props from memory.

---

# 16. UI Principles

Health is:

> Minimalistic and powerful.

Menus should normally contain no more than five actions.

Prefer:

* short contextual menus
* horizontal action groups
* progressive disclosure
* icons combined with meaningful labels

All actions except switching between primary application views must remain reachable even when the side navigation is collapsed.

Every button requires a meaningful label or accessible name.

Accessibility takes priority over visual compactness.

---

# 17. Accessibility

Core functionality must support:

* keyboard navigation
* screen readers
* logical focus order
* visible focus indicators
* browser zoom
* responsive layouts
* sufficient contrast
* color-independent status communication

Do not implement clickable non-semantic elements where a proper interactive component exists.

---

# 18. Internationalization

Every visible user-facing string must use the Nextcloud translation system.

Do not hard-code interface strings directly into Vue components.

This includes:

* buttons
* labels
* errors
* empty states
* notifications
* chart labels
* settings text

---

# 19. Charts

Chart.js is the only charting framework permitted.

Do not add:

```text
vue-chartjs
echarts
apexcharts
highcharts
d3
```

without an explicit architectural decision.

Charts are supplementary visualizations.

Important statistical information must also be available as text.

---

# 20. Frontend State

Keep frontend state minimal.

Prefer local state unless state is genuinely shared.

Do not introduce a large global state architecture without demonstrated need.

Server data remains authoritative.

Domain logic belongs primarily in backend services.

---

# 21. Frontend API Layer

Do not scatter HTTP calls throughout Vue components.

Use a dedicated API client layer.

Conceptually:

```text
Vue component
      ↓
Health API client
      ↓
OCS API v2
```

Suggested structure:

```text
src/api/
```

Components consume typed API methods.

---

# 22. Database

Use official Nextcloud database APIs.

Schema changes must use migrations.

Migration files belong in:

```text
lib/Migration/
```

Do not:

* execute schema-changing SQL during normal requests
* access private Nextcloud database internals
* assume one database vendor

---

# 23. Initial Tables

The MVP should initially remain limited to approximately:

```text
health_entries
health_user_metrics
health_goals
health_reminders
```

Do not add tables unless there is a concrete domain requirement.

Do not add statistics cache tables prematurely.

---

# 24. API Tests

API tests are the primary automated testing requirement for the MVP.

Every public endpoint must have API coverage.

Tests must cover:

* authenticated access
* successful operation
* invalid input
* ownership
* cross-user access
* response structure
* pagination where applicable

Use at least two test users for ownership tests.

---

# 25. Security Tests

Cross-user tests are mandatory.

For example:

1. User A creates an entry.
2. User B cannot read it.
3. User B cannot update it.
4. User B cannot delete it.
5. User A can still access it.

Never disable or weaken these tests to make implementation pass.

---

# 26. OpenAPI Quality Gate

The official Nextcloud OpenAPI extraction process must succeed.

Do not:

* suppress type errors
* remove documentation
* weaken response definitions

to make tooling pass.

Fix the underlying implementation.

---

# 27. Notifications

Health reminders use only official Nextcloud Notifications.

Do not build:

* a custom push service
* a separate notification daemon
* calendar-dependent reminders
* an external queue

Notification text must not reveal personal Health values.

---

# 28. External Clients

Health API v2 supports authenticated external clients such as:

* mobile applications
* desktop applications
* CLI clients

Do not enable unrestricted browser CORS.

Do not weaken CSRF protection for convenience.

---

# 29. Capabilities

Expose Health functionality through Nextcloud Capabilities where appropriate.

Capabilities may describe:

* API versions
* modules
* supported features

Capabilities must never expose personal Health data.

---

# 30. Source of Truth Priority

When information conflicts, use this priority:

1. repository security requirements
2. repository architecture documents
3. official Nextcloud 33 documentation
4. official Nextcloud APIs
5. installed `@nextcloud/vue` implementation/documentation
6. maintained Nextcloud reference applications
7. legacy Health implementation
8. model memory

Never choose model memory over verifiable current source code or documentation.

---

# 31. Reference Implementations

# Reference Implementations

Reference repositories are available outside this repository and are
READ-ONLY reference material.

From the Health repository they are located at:

../../../../../references/

## Health Legacy

Path:

../../../../../references/health-legacy

Use only to understand:

- previous Health functionality
- terminology
- legacy data structures
- previous user experience
- migration requirements

Do not copy its architecture blindly.
Do not modify this repository.

## Nextcloud Notes

Path:

../../../../../references/notes

Use as reference for:

- maintained Nextcloud PHP app patterns
- API-first application architecture
- Vue frontend structure
- external-client-friendly APIs

Do not modify this repository.

## Nextcloud Tables

Path:

../../../../../references/tables

Use as reference for:

- OCS API patterns
- controllers
- services
- entities and mappers
- larger Vue 3 application structure
- compact contextual menus
- Nextcloud UI interaction patterns

Do not modify this repository.

## Nextcloud Vue

Path:

../../../../../references/nextcloud-vue

Use as the primary source of truth for:

- available @nextcloud/vue components
- component properties
- component events
- accessibility patterns
- component usage examples

Before implementing a custom UI control, search this repository for an
appropriate Nextcloud Vue component.

Do not invent @nextcloud/vue component APIs from model memory.

Do not modify this repository.

## Reference Rules

Reference repositories are read-only.

Agents must never:

- commit changes to reference repositories
- modify files inside reference repositories
- copy large sections of code without understanding them
- assume that a reference implementation is compatible with Nextcloud 33

When implementing Nextcloud-specific functionality:

1. check the repository specifications
2. check official Nextcloud 33 APIs and documentation
3. inspect relevant reference implementations
4. verify compatibility with Nextcloud 33
5. implement the smallest appropriate solution in Health

---

# 32. Do Not Invent Nextcloud APIs

Before using a Nextcloud class, attribute, interface, service or Vue component:

1. verify that it exists
2. verify that it supports Nextcloud 33
3. inspect the official signature
4. follow current documented usage

Never invent plausible-looking Nextcloud APIs.

---

# 33. Implementation Order

For each new feature, follow this order:

```text
1. Read relevant specification
2. Define or confirm API contract
3. Define response types
4. Implement domain/service behavior
5. Implement persistence
6. Implement OCS endpoint
7. Add API tests
8. Verify OpenAPI extraction
9. Implement frontend API client
10. Implement Vue UI
11. Verify responsive and accessible behavior
```

Do not begin with a polished Vue screen and invent the backend afterward.

---

# 34. Scope Discipline

Implement only the requested slice.

Do not expand tasks into unrelated features.

Example:

If asked to implement Stress entry creation, do not also implement:

* Mood
* Weight
* Sleep
* Goals
* Statistics
* Reminders

unless required by the architecture of the requested slice.

Keep changes small and reviewable.

---

# 35. No Premature Abstraction

Do not create frameworks for hypothetical future needs.

Prefer simple explicit code.

Generalize only when at least two concrete cases demonstrate the abstraction.

Health should remain understandable to a human maintainer.

---

# 36. Existing Code

Before replacing existing code:

1. understand why it exists
2. inspect its callers
3. inspect tests
4. determine whether it follows current architecture

Do not rewrite code merely because a different implementation looks cleaner.

---

# 37. Changes to Architecture Documents

Do not silently modify:

```text
PRODUCT.md
MVP.md
ARCHITECTURE.md
DATA_MODEL.md
API.md
SECURITY.md
UI.md
```

to make implementation easier.

If implementation reveals a genuine specification problem:

1. explain the conflict
2. propose the smallest specification change
3. keep product/security intent intact

Architecture changes deserve explicit review.

---

# 38. Definition of Done

A feature is complete only when:

* it follows repository specifications
* API behavior matches `API.md`
* user ownership is enforced
* server-side validation exists
* relevant API tests pass
* OpenAPI extraction succeeds
* frontend uses the public OCS API
* standard UI uses `@nextcloud/vue`
* visible strings are translatable
* accessibility requirements are respected
* no Health data is logged
* no unrelated scope was added
* OpenAPI artifacts have been regenerated and are up to date when API response or type definitions change

---

# 39. Agent Reporting

After completing a task, report:

1. what was implemented
2. files changed
3. API endpoints added or changed
4. migrations added
5. tests added
6. commands/checks executed
7. any unresolved issue
8. any architecture decision that requires human review

Do not report a task as complete if required checks failed.

---

# 40. First Development Slice

The first implementation slice for Health v2 is:

> Record a Stress value from 1 to 5 on the Today screen and display the saved entry in today's journal.

The slice must establish the full vertical architecture:

```text
Metric definition
      ↓
Entry persistence
      ↓
Service
      ↓
OCS API v2
      ↓
API test
      ↓
OpenAPI documentation
      ↓
Frontend API client
      ↓
Vue 3 Today UI
      ↓
Journal display
```

Do not implement additional metrics as part of this slice unless explicitly requested.

---

# 41. Development Environment and Verification

`AGENTS.md` is the authoritative development instruction source for coding
agents. `CLAUDE.md` intentionally references this file rather than maintaining
a second, potentially divergent copy of these rules.

Development must not depend on globally installed host PHP, Composer, or
Node.js. Run commands from the Health repository root, using the existing
development container where available or generic Docker mounts for fixed
runtimes. Do not add developer-specific absolute paths to repository
documentation or scripts.

Run Composer inside the existing `nextcloud` Docker Compose service, from the
Health directory mounted in that container. For example, when Docker Compose
can discover its project:

```bash
docker compose exec -T nextcloud sh -lc 'cd /var/www/html/apps-extra/health && composer install'
```

Do not mutate `composer.json` or `composer.lock` merely to reproduce CI in the
active worktree. In particular, do not routinely run CI matrix preparation
commands such as `composer remove nextcloud/ocp --dev --no-scripts` there. The
complete OCP-version matrix is CI's responsibility unless it is explicitly
reproduced in an isolated temporary environment.

## PHP and Psalm

The application may be developed and tested with newer PHP versions in a
Nextcloud development instance. Psalm is different: `psalm.xml` declares PHP
8.2, and Psalm 5.26.x is known to fail when run with the PHP 8.5 development
container. Psalm must therefore run with PHP 8.2 using this exact command:

```bash
docker run --rm \
  -v "$PWD":/app \
  -w /app \
  php:8.2-cli \
  php vendor/bin/psalm \
    --threads=1 \
    --no-cache \
    --monochrome \
    --no-progress
```

The required successful result is `No errors found!`. Psalm must reach zero
errors before an implementation task is complete. Never lower the configured
level, globally suppress issue categories, replace precise API response types
with `mixed`, or add broad suppressions merely to obtain green CI. A narrowly
scoped and justified suppression is permitted only for a genuine Nextcloud
lifecycle false positive, such as dependency injection, Entity/Mapper
hydration, migrations, background jobs, Dashboard widgets, Unified Search
providers, Notifications, or capabilities.

Run PHP coding standards through that service:

```bash
docker compose exec -T nextcloud sh -lc 'cd /var/www/html/apps-extra/health && composer run cs:check'
```

PHP-CS failures are mandatory. If they are formatting-only failures, use the
configured fixer, then verify the result:

```bash
docker compose exec -T nextcloud sh -lc 'cd /var/www/html/apps-extra/health && composer run cs:fix'
docker compose exec -T nextcloud sh -lc 'cd /var/www/html/apps-extra/health && composer run cs:check'
```

Do not manually imitate PHP-CS formatting when the configured fixer can make
the correction.

Run PHP unit tests through that service:

```bash
docker compose exec -T nextcloud sh -lc 'cd /var/www/html/apps-extra/health && composer run test:unit'
```

Do not use `composer run test`: this repository does not define that script.
The unit suite fails on warnings and risky tests, so a pass has no failures,
errors, warnings, or risky tests.

## OpenAPI Artifacts

Run OpenAPI generation as a mandatory verification step:

```bash
docker compose exec -T nextcloud sh -lc 'cd /var/www/html/apps-extra/health && composer run openapi'
```

The command must succeed without fatal extractor errors. Non-fatal extractor
warnings do not block a pass unless the command exits non-zero. API response or
type-definition changes require regeneration of OpenAPI artifacts.

Generated `openapi*.json` files are version-controlled. When regeneration
changes them, retain and commit those generated changes with the corresponding
source change. After generation, verify that the generated OpenAPI artifacts
are up to date; do not hand-edit them to make that check pass.

## Frontend

Frontend development uses Node.js 24 (and the repository-declared npm version).
Prefer an ephemeral Node 24 Docker environment instead of requiring host npm;
for example:

```bash
docker run --rm -u "$(id -u):$(id -g)" -v "$PWD":/app -w /app node:24-bookworm npm ci
```

The required frontend checks are the scripts defined in `package.json`:

```bash
npm run typecheck
npm run lint
npm run stylelint
npm run build
```

Compiled production frontend assets are intentionally version-controlled in
Health. After frontend source changes, run `npm run build` and include the
generated runtime assets in the change where applicable. Never commit
`node_modules`, and do not delete generated production bundles merely because
they are build artifacts.

## Final Verification Order

Before reporting an implementation task complete, run the applicable checks in
this order:

1. `docker compose exec -T nextcloud ... composer run cs:check`
2. Psalm with the PHP 8.2 Docker command above
3. `docker compose exec -T nextcloud ... composer run test:unit`
4. `docker compose exec -T nextcloud ... composer run openapi`
5. verify generated `openapi*.json` artifacts are up to date
6. `npm run typecheck`
7. `npm run lint`
8. `npm run stylelint`
9. `npm run build`
10. `git diff --check`
11. `git status --short`

If `cs:check` needs formatting corrections, run `composer run cs:fix` through
the `nextcloud` service, then rerun `cs:check` and Psalm. `git diff --check` is
mandatory and must produce no whitespace errors. A task must not be reported
complete while a required check fails. If a check genuinely does not apply,
explain why in the completion report; do not hide failures by weakening
configuration.

`scripts/verify.sh` provides this final verification sequence. It locates the
Health repository from its own location, discovers the nearest enclosing Docker
Compose project, and uses its running `nextcloud` service for Composer checks.
It never fixes formatting, changes dependencies, resets/cleans/stashes Git
state, or commits.

---

# 42. Git Commit Policy

Codex must not create a commit unless the user explicitly asks for one. When a
commit is requested, every commit must use Conventional Commits and include a
DCO sign-off produced by Git:

```bash
git commit -s -m "<type>: <description>"
```

Use an appropriate prefix such as `feat:`, `fix:`, `docs:`, `style:`, `test:`,
`refactor:`, `chore:`, `build:`, or `ci:`. Use lowercase description wording
where practical. Unprefixed subjects such as `Update README`, `Add feature`,
or `Fix issue` are not acceptable.

Examples:

```text
feat: add support dialog
fix: correct search result deep link
docs: update README
style: apply PHP coding standards
test: add search provider regression test
refactor: improve goal response typing
chore: update development tooling
```

Use `git commit -s` rather than manually fabricating the required
`Signed-off-by: <Git user name> <Git user email>` trailer. Do not invent a
person's name or email. To preserve an existing commit message while adding a
sign-off, use `git commit --amend -s --no-edit`; to change it, use
`git commit --amend -s -m "fix: correct ..."`.

Do not rewrite shared history automatically. If an already-pushed commit must
be rewritten for DCO or Conventional Commit compliance, use interactive rebase
or amend, verify the resulting history, then use `git push --force-with-lease`.
Never recommend plain `git push --force` when `--force-with-lease` is
sufficient. For newly appended commits with no rewritten history, use ordinary
`git push`.

## Completion Report

Finish coding tasks with a compact report containing:

```text
Changed:
- relevant files/features

Verification:
- PHP CS: PASS / FAIL / N/A
- Psalm PHP 8.2: PASS / FAIL / N/A
- PHP unit tests: PASS / FAIL / N/A
- OpenAPI generation: PASS / FAIL / N/A
- OpenAPI artifacts up to date: PASS / FAIL / N/A
- TypeScript: PASS / FAIL / N/A
- ESLint: PASS / FAIL / N/A
- Stylelint: PASS / FAIL / N/A
- Production build: PASS / FAIL / N/A
- git diff --check: PASS / FAIL

Git:
- commit created: yes/no
- if yes: commit hash and subject
- DCO sign-off present: yes/no
```

Never claim `PASS` for a command that was not executed.
