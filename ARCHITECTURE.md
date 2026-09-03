# Health v2 – Architecture

## 1. Purpose

Health is a classic Nextcloud PHP application with a Vue 3 frontend.

The application is designed API-first. The web frontend is one client of the public Health API and must not use private or undocumented backend interfaces.

The architecture should remain simple enough to support AI-assisted development while maintaining clear boundaries, testability, API stability, and compatibility with Nextcloud.

---

## 2. Platform

### Minimum Nextcloud Version

Health requires:

* Nextcloud 33 or newer

All implementation decisions must use APIs officially supported by the minimum supported Nextcloud version.

Private Nextcloud internals must not be used.

When functionality is available through an official Nextcloud API, that API must be preferred over custom implementations.

### Application Version

The first Health v2 application release uses version:

```text
3.0.0
```

The legacy application with the same `health` app ID reached version 2.2.2. The application release version is therefore independent of the Health API version, which starts at v2.

---

## 3. Application Type

Health is a classic Nextcloud application.

Backend:

* PHP
* Nextcloud App Framework
* Nextcloud database APIs
* OCS API

Frontend:

* Vue 3
* TypeScript
* `@nextcloud/vue`

Health does not use AppAPI or ExApps.

---

# 4. API-first Architecture

The Health API is the central interface between backend and clients.

All user-facing data operations must be available through the public Health OCS API.

This includes operations used by:

* the Nextcloud Vue frontend
* future mobile applications
* desktop applications
* command-line clients
* other Nextcloud applications
* other authenticated API clients

The Vue frontend must use the same public API as external clients.

There must not be a separate private JSON API exclusively for the frontend.

---

# 5. API Version

The legacy Health application already exposed an API identified as version 1.

Health v2 therefore starts with:

`Health API v2`

The base path should follow the Nextcloud OCS convention:

```text
/ocs/v2.php/apps/health/api/v2/
```

Example:

```text
GET /ocs/v2.php/apps/health/api/v2/entries
POST /ocs/v2.php/apps/health/api/v2/entries
PUT /ocs/v2.php/apps/health/api/v2/entries/{id}
DELETE /ocs/v2.php/apps/health/api/v2/entries/{id}
GET /ocs/v2.php/apps/health/api/v2/modules
GET /ocs/v2.php/apps/health/api/v2/statistics
```

The `v2.php` portion belongs to the Nextcloud OCS protocol.

The `/api/v2/` portion identifies the Health API version.

Breaking API changes require a new Health API version.

API v2 should remain backwards compatible after release whenever reasonably possible.

---

# 6. OCS

All public Health endpoints must use the official Nextcloud OCS architecture.

Controllers providing API endpoints must extend the appropriate Nextcloud OCS controller classes.

Responses must use official Nextcloud response objects.

Do not create custom response envelopes when an OCS response can be used.

---

# 7. API Documentation

Every public API endpoint must be documented directly from the source code.

Health must use the official Nextcloud OpenAPI extraction workflow.

API code must contain all required:

* PHP types
* PHPDoc types
* response definitions
* descriptions
* parameter descriptions
* Nextcloud attributes
* OpenAPI-related metadata

Reusable response structures should be centrally defined where appropriate.

The generated OpenAPI documentation is considered part of the public API.

A public endpoint is not considered complete if it cannot be successfully processed by the Nextcloud OpenAPI extractor.

---

# 8. API Discovery

Health must expose its relevant capabilities through the Nextcloud Capabilities mechanism.

External clients should be able to determine at least:

* whether Health is installed
* supported Health API versions
* currently supported built-in metrics
* optional Health API features

Example conceptual capability:

```json
{
  "health": {
    "apiVersions": [
      "2"
    ],
    "metrics": [
      "stress",
      "energy",
      "mood",
      "hydration",
      "break",
      "movement",
      "sleep",
      "weight"
    ]
  }
}
```

The exact structure may evolve during implementation.

---

# 9. Authentication

Health relies on official Nextcloud authentication mechanisms.

External clients such as mobile applications must be able to use the API with supported Nextcloud authentication methods.

Health must not implement its own authentication system.

The API must never accept a user ID supplied by the client as authorization to access Health data.

The authenticated Nextcloud user determines ownership of all personal Health data.

---

# 10. Browser Access and CORS

Health supports authenticated external clients such as:

* mobile applications
* desktop applications
* CLI applications
* server-side integrations

Health does not enable unrestricted browser CORS access.

Arbitrary third-party websites must not be able to call the Health API directly from JavaScript.

Do not disable Nextcloud CSRF or authentication protections merely to make browser integrations easier.

CORS support may only be introduced later for a concrete and reviewed use case.

---

# 11. User Model

Health has no separate person or profile ownership model.

All personal Health data belongs directly to the authenticated Nextcloud user.

There are no:

* shared health profiles
* arbitrary ACLs
* manager access
* administrator access through Health
* employer access
* delegated health profiles

Users can only access their own Health data through the application API.

---

# 12. Backend Layers

The backend follows a strict layered architecture:

```text
OCS Controller
      ↓
Service
      ↓
Mapper / Repository
      ↓
Database
```

## Controller

Controllers are responsible for:

* receiving requests
* validating request structure
* invoking services
* returning OCS responses

Controllers must remain thin.

Controllers must not contain business logic.

Controllers must not contain SQL.

---

## Service

Services contain application and domain logic.

Examples:

* validating metric values
* creating journal entries
* calculating daily summaries
* evaluating goals
* calculating statistics

Services must not depend on HTTP request objects.

The same service logic should be reusable from other internal contexts.

---

## Mapper / Repository

Mappers are responsible for persistence.

They:

* read database entities
* store entities
* update entities
* delete entities
* execute database queries

Business rules must not be implemented in mappers.

## Saved Statistics Views

Saved Statistics views are ordinary owner-scoped configuration resources. Their controller delegates validation and lifecycle operations to a service, which uses an owner-scoped mapper. They store only a title, icon, selected stable metric identifiers, and a Statistics period preset; Statistics values remain derived on demand.

---

# 13. Modules and Generic Metric Model

Health distinguishes between **modules** and **metrics**.

A module is a user-facing tracking feature.

Examples:

```text
stress
energy
mood
hydration
break
movement
sleep
weight
```

A metric represents one atomic measurable value or event.

Examples:

```text
stress
energy
mood
hydration
break
movement
steps
sleep_duration
sleep_recovery
weight
```

A simple module may contain exactly one metric.

Example:

```text
Module: stress
Metric: stress
```

A composite module may contain multiple metrics.

Example:

```text
Module: sleep

Metrics:
- sleep_duration
- sleep_recovery
```

The frontend may present multiple related metrics as one user interaction.

For example, the Sleep module may ask for sleep duration and recovery in a single form.

Internally these values are stored as separate atomic entries.

Health must not store complex metric values as arbitrary JSON merely because a module contains multiple values.

Do not create a separate backend architecture for every module or metric.

Avoid tables such as:

```text
health_weight
health_stress
health_energy
health_water
health_sleep
```

Instead, measurements are stored through the common Entry model.

This architecture allows future composite modules such as Blood Pressure:

```text
Module: blood_pressure

Metrics:
- blood_pressure_systolic
- blood_pressure_diastolic
- pulse
```

without requiring new measurement tables.


---

# 14. Built-in Module and Metric Definitions

Built-in modules and metrics have stable identifiers.

Initial module identifiers:

```text
stress
energy
mood
hydration
break
movement
sleep
weight
```

Initial metric identifiers:

```text
stress
energy
mood
hydration
break
movement
steps
sleep_duration
sleep_recovery
weight
```

Module and metric identifiers form part of the public API and must not be changed casually.

## Module Definitions

A module definition describes a user-facing tracking feature.

Conceptually it may contain:

```text
moduleKey
metrics
label
description
inputBehavior
```

Example:

```text
moduleKey: sleep

metrics:
- sleep_duration
- sleep_recovery
```

## Metric Definitions

A metric definition describes one atomic measurable value or event.

Conceptually it contains information such as:

```text
metricKey
moduleKey
valueType
unit
minimum
maximum
allowedOptions
aggregation
```

Example:

```text
metricKey: stress
moduleKey: stress
valueType: scale
minimum: 1
maximum: 5
aggregation: average
```

Example:

```text
metricKey: sleep_duration
moduleKey: sleep
valueType: duration
unit: minutes
aggregation: average
```

Example:

```text
metricKey: sleep_recovery
moduleKey: sleep
valueType: scale
minimum: 1
maximum: 5
aggregation: average
```

Example:

```text
metricKey: hydration
moduleKey: hydration
valueType: event

allowedOptions:
- small_glass
- large_glass
- coffee
- cappuccino
- espresso
- double_espresso
- latte_macchiato
- cafe_au_lait
- tea
- other

aggregation: count
```

The implemented Break metric is also an event metric. Its allowed options are `short`, `regular`, `short_walk`, `long_walk`, `mindfulness`, and `fresh_air`; it uses count aggregation. These are atomic entries and do not require a separate endpoint or table.

Module and metric definitions are application definitions and are not database records.

The database stores user configuration and recorded values, not the fundamental product definitions.

Metric definitions are authoritative for backend validation.

---

# 15. Entry Model

A journal entry represents one recorded event or measurement.

Conceptually an entry contains:

```text
id
userId
metricKey

numericValue
optionValue

context
source

recordedAt
createdAt
updatedAt

note
```

Not every field applies to every metric.

The metric definition determines which value type is valid.

`source` records the creation channel (`web`, `api`, `mobile`, or `notification`). It is informational only and must not participate in authentication, authorization, or ownership decisions.

---

# 16. Entry Context

Entries may optionally contain a context.

Initial contexts include:

```text
manual
checkin
checkout
reminder
```

This makes it possible to distinguish, for example:

* manually recorded stress
* morning check-in stress
* evening check-out stress

without creating separate storage systems.

---

# 17. Metric Value Validation

Metric values must be validated by the backend.

The frontend must not be trusted as the only validation layer.

Examples:

Stress:

```text
1–5
```

Weight:

```text
numeric value
```

Hydration:

```text
one of:
small_glass
large_glass
coffee
cappuccino
espresso
double_espresso
latte_macchiato
cafe_au_lait
tea
other
```

Unknown metrics or unsupported options must be rejected.

---

# 18. User Metric Configuration

Users decide which metrics they use.

A user metric configuration may contain:

```text
metricKey
enabled
showInQuickEntry
includeInCheckin
includeInCheckout
displayOrder
```

Additional configuration can be added later without changing the underlying entry architecture.

Until persistent module configuration is implemented, the Stress module is initially enabled for a new user.

---

# 19. Goals

Goals are separate from recorded measurements.

A goal references a metric.

Examples:

```text
hydration:
6 events per day
```

```text
break:
3 events per day
```

```text
checkin:
complete once per workday
```

Goal evaluation belongs in the service layer.

---

# 20. Reminders

Health uses the official Nextcloud Notifications infrastructure for user reminders.

Health must not implement:

* its own notification daemon
* external push infrastructure
* calendar-dependent scheduling
* external task queues

Reminder settings belong to the user.

Examples:

```text
Morning check-in: 08:30
Hydration reminder: 11:00
Break reminder: 14:00
Check-out: 17:00
```

Background processing should use official Nextcloud mechanisms where required.

---

# 21. Time Handling

Health distinguishes between:

`recordedAt`

and:

`createdAt`

`recordedAt` describes when the health measurement or event occurred.

`createdAt` describes when the database record was created.

Example:

A user may enter at 19:00 that their stress level was 4 at 14:00.

In this case:

```text
recordedAt = 14:00
createdAt = 19:00
```

Time storage must be unambiguous.

Times are displayed according to the user's Nextcloud timezone.

The API accepts explicit RFC3339 date-time values and returns timestamps canonically in RFC3339 UTC form using `Z`.

Time ranges use half-open `[from, to)` semantics: `from` is inclusive and `to` is exclusive.

---

# 22. Entry Modification

Users may:

* create entries
* read entries
* update entries
* delete entries

There is no immutable medical audit log in the MVP.

All operations remain restricted to the authenticated owner.

Update and delete lookups bind both the entry ID and authenticated user ID. A missing entry and another user's entry produce the same not-found behavior.

---

# 23. Pagination

Endpoints returning journal entries must be paginated.

Health should use cursor-based pagination rather than loading the entire journal.

Example concept:

```text
GET /entries?limit=50
```

Response:

```text
entries: [...]
nextCursor: "..."
```

Next request:

```text
GET /entries?limit=50&cursor=...
```

The cursor format is an implementation detail and must be treated as opaque by clients.

Default and maximum page sizes must be defined.

---

# 24. Statistics

Statistics are calculated from recorded entries.

The initial architecture supports descriptive statistics only.

Examples:

* average
* minimum
* maximum
* count
* daily total
* goal completion
* value over time

Statistics must never imply medical diagnosis or causation.

Example:

Allowed:

```text
Average stress this week: 3.1
```

Not allowed:

```text
Your stress increased because you slept less.
```

---

# 25. Aggregation

Aggregation behavior belongs to metric definitions.

Examples:

```text
stress → average
energy → average
mood → average
weight → latest / trend
hydration → count
break → count
movement → count
steps → sum or latest depending on recording semantics
sleep_duration → average
sleep_recovery → average
```

Aggregation is defined per atomic metric, not per module. A module containing multiple metrics may therefore expose multiple independent statistics.

Aggregation logic belongs in services, not Vue components.

---

# 26. Frontend Architecture

The frontend uses:

* Vue 3
* TypeScript
* official Nextcloud frontend libraries

The frontend communicates exclusively with Health OCS API v2 for persistent application data.

Vue components must not access the database or backend implementation concepts directly.

---

# 27. Nextcloud Vue Components

The UI must use `@nextcloud/vue`.

If an appropriate Nextcloud Vue component exists, it must be used.

Do not replace available Nextcloud components with:

* custom buttons
* custom modals
* custom dropdowns
* custom inputs
* alternative UI frameworks
* competing component libraries

Custom Vue components are allowed only for Health-specific UI that does not exist in `@nextcloud/vue`.

Examples:

* MetricInput
* DailyMetricStatus
* HealthHeatmap
* MetricChart

Custom components should still use Nextcloud design primitives internally where possible.

---

# 28. No Additional UI Framework

Do not introduce:

* Vuetify
* Bootstrap
* Tailwind UI
* Quasar
* Material UI
* PrimeVue
* other component frameworks

`@nextcloud/vue` is the sole general-purpose UI component library.

---

# 29. Charts

Health uses exactly one dedicated charting library:

**Chart.js**

Do not add a second chart framework.

Do not add an additional Vue wrapper unless there is a demonstrated technical need.

Prefer direct Chart.js integration through a small Health-specific Vue component.

Chart.js is used for:

* metric history
* line charts
* bar charts
* simple statistical visualization

Simple status indicators and calendar heatmaps may be implemented with Vue and CSS when Chart.js is not appropriate.

---

# 30. Frontend State

Global frontend state must remain minimal.

Do not introduce global state simply because data may be shared between two components.

Local component state should be preferred where practical.

If a centralized state solution is required, its use must be justified by actual shared application state.

Persistent server data remains authoritative.

The frontend must not duplicate domain logic already implemented by backend services.

---

# 31. API Client Layer

Vue components must not scatter raw HTTP calls throughout the application.

The frontend should have a dedicated API client layer.

Conceptually:

```text
Vue Component
      ↓
Health API Client
      ↓
OCS API v2
```

Example:

```text
src/
├── api/
│   ├── entries.ts
│   ├── metrics.ts
│   ├── goals.ts
│   └── statistics.ts
```

Components consume typed API functions.

---

# 32. API Types

Request and response types should be explicit.

TypeScript types should reflect the public API.

Where practical, generated OpenAPI definitions should become the reference for client types.

Avoid duplicating incompatible manual definitions of the same API contract.

---

# 33. Database

Health uses official Nextcloud database APIs.

Database schema changes must use official Nextcloud migrations.

Do not:

* execute schema mutations at runtime
* use raw database connections outside supported APIs
* depend on a specific database engine where Nextcloud provides abstraction

The schema must remain compatible with Nextcloud-supported database systems.

---

# 34. Initial Persistence Model

The initial design should require approximately these conceptual persistence areas:

```text
health_entries
health_user_metrics
health_goals
health_reminders
```

The exact schema will be defined in `DATA_MODEL.md`.

Avoid adding additional tables until a real domain requirement justifies them.

---

# 35. Privacy

Privacy is an architectural requirement.

Every Health query must be scoped to the authenticated user.

Never implement repository methods such as:

```text
getAllHealthEntries()
```

without an explicit and safe user scope.

Prefer methods conceptually resembling:

```text
findForUser(...)
findEntryForUser(...)
deleteEntryForUser(...)
```

Authorization must not depend solely on frontend behavior.

---

# 36. Administrator Access

Nextcloud administrators do not receive a Health-specific interface for browsing user health data.

There is no Health administrator health-data API.

Administrative configuration and personal health information are separate concerns.

---

# 37. Encryption

Health does not implement an additional application-specific encryption layer in the MVP.

Do not invent custom cryptography.

The architecture should avoid unnecessary assumptions that would prevent encrypted storage from being introduced in a later version.

---

# 38. Tests

The initial automated testing priority is the public Health API.

API tests are mandatory for public endpoints.

Tests should verify at minimum:

* authentication
* user isolation
* input validation
* successful creation
* reading
* updating
* deletion
* pagination
* response structure
* expected OCS behavior

A new public endpoint is not complete without appropriate API coverage.

Frontend component tests and extensive backend unit testing may be introduced later.

---

# 39. Cross-user Security Tests

API tests must explicitly verify that one user cannot:

* read another user's entries
* update another user's entries
* delete another user's entries
* read another user's goals
* access another user's statistics

These tests are considered security-critical.

---

# 40. OpenAPI Quality Gate

Every public endpoint must successfully pass the official Nextcloud OpenAPI extraction process.

API changes must keep generated documentation valid.

Psalm/type information required by the OpenAPI tooling must remain valid.

Do not suppress OpenAPI or type errors merely to make checks pass.

Fix the underlying API definition.

---

# 41. Definition of Done for API Work

An API change is complete only when:

1. the endpoint uses OCS
2. authentication and authorization are correct
3. input is validated server-side
4. response types are explicit
5. OpenAPI metadata is complete
6. OpenAPI extraction succeeds
7. API tests pass
8. cross-user access is tested where applicable
9. the Vue frontend uses the public endpoint if the feature is exposed in the web UI

---

# 42. Architecture Rule for AI Coding Agents

When implementing a feature, agents must follow this order:

```text
Public API contract
        ↓
Domain / Service behavior
        ↓
Persistence
        ↓
API tests
        ↓
Frontend API client
        ↓
Vue UI
```

Do not begin by implementing the Vue interface and invent the backend afterward.

---

# 43. External References

When implementing Nextcloud-specific functionality:

1. Use the current Nextcloud 33 documentation.
2. Use official Nextcloud APIs.
3. Inspect current maintained Nextcloud applications for established patterns.
4. Never invent a Nextcloud API based on model memory.
5. Never use private Nextcloud internals merely because they appear easier.

Compatibility with Nextcloud 33 is the minimum requirement.

---

# 44. Architectural Priorities

When architectural goals conflict, use this priority:

1. User privacy
2. Correctness and data isolation
3. Public API stability
4. Official Nextcloud patterns
5. Simplicity
6. Maintainability
7. Developer convenience
8. Feature richness

Health should prefer a smaller, understandable implementation over unnecessary abstraction.

---

## Development Environment

Primary local development may run on Nextcloud 35.

However, all production code must remain compatible with the documented
minimum version, Nextcloud 33.

Agents must not use APIs introduced after Nextcloud 33 unless an explicit
compatibility layer is implemented.

Minimum-version compatibility must be verified separately before release.

## Daily note slice

Daily notes use the existing public OCS v2 controller → service → mapper layers. `health_daily_notes` is a separate owner-scoped table because notes are one text value per local day, not metric entries.

## Native Nextcloud integrations

Health registers native integrations through the public Nextcloud bootstrap APIs. The Daily Note Unified Search provider implements `OCP\Search\IProvider`; it first checks the authenticated user's owner-scoped `searchDailyNotes` configuration preference, then performs a user-scoped, portable case-insensitive search of Daily Notes. The preference defaults to `false`, and the provider returns no results without querying notes when it is disabled.

The Health Dashboard integration implements `OCP\Dashboard\IWidget` and loads a dedicated Health dashboard asset. The browser widget is an ordinary Health API client: it uses existing typed OCS clients for the user's configuration, today’s entries, measurements, and daily values, and does not receive Health data through the dashboard bootstrap.

## Browser routing

The Health Vue client uses browser-history routing rooted at the Nextcloud-generated Health application URL. The application shell has authenticated frontpage routes for `/`, `/journal/{date}`, `/goals`, `/statistics`, and `/settings`; these routes only render the frontend shell and never duplicate Health data APIs. Statistics v3 replaces the former Overview route. The Journal route date is the canonical selected-day state. Selecting Journal in the application navigation always creates today's canonical Journal route rather than preserving a historical route date. Invalid or future dates are replaced client-side with today's route after the shell has loaded.

## Goals and reminders

Goals use the public OCS v2 controller → service → mapper layers. `GoalTargetRegistry` is the sole source of supported public target identifiers and their permitted periods, comparators, units, and source metrics. `GoalProgressService` derives owner-scoped progress directly from entries, daily values, and measurements; no progress cache is persisted. Goal revisions retain the local period in which a target change became effective. `GoalReminderJob` is an hourly official Nextcloud `TimedJob` that evaluates deterministic, privacy-safe policy through the Notifications API. The Vue Goals view and Journal indicators consume only typed Health OCS clients.
