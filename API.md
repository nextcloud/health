# Health v2 – Public API

## 1. Purpose

Health exposes a versioned public OCS API.

The API is the canonical interface for all persistent Health functionality.

It is used by:

* the Health Vue frontend
* future mobile applications
* desktop applications
* command-line clients
* other Nextcloud applications
* authenticated third-party integrations

There is no separate private data API for the Health frontend.

---

# 2. API Version

The legacy Health application exposed API version 1.

The new Health API therefore starts with:

```text
Health API v2
```

Base path:

```text
/ocs/v2.php/apps/health/api/v2
```

Example:

```text
GET /ocs/v2.php/apps/health/api/v2/entries
```

The first `v2.php` is the Nextcloud OCS protocol endpoint.

The second `/v2/` is the Health API version.

---

# 3. OCS Requirements

All public Health API controllers must:

* extend `OCSController`
* return `DataResponse`
* use official Nextcloud authentication
* use official Nextcloud OCS exceptions
* contain explicit PHP types
* contain complete OpenAPI-compatible documentation
* be extractable by the Nextcloud OpenAPI extractor

Do not introduce custom JSON response envelopes.

---

# 4. Route Declaration

For Nextcloud 33, API routes should preferably be defined using:

```php
#[ApiRoute(...)]
```

directly on controller methods.

Routes must be explicit and versioned.

Example concept:

```php
#[ApiRoute(
    verb: 'GET',
    url: '/api/v2/entries'
)]
```

Do not expose unversioned public Health API routes.

---

# 5. Authentication

All Health API endpoints require an authenticated Nextcloud user unless explicitly documented otherwise.

The authenticated Nextcloud account determines ownership.

Clients must never provide an authoritative user ID.

Invalid:

```json
{
  "userId": "alice",
  "metricKey": "stress",
  "numericValue": 4
}
```

The server derives the user from the authenticated request.

---

# 6. External Clients

The Health API is designed to support external authenticated clients.

Examples:

* Android application
* iOS application
* desktop application
* CLI client

Health does not enable unrestricted CORS access for arbitrary websites.

---

# 7. Response Convention

All endpoint definitions in this document describe the payload contained in the OCS `data` element.

Health must not manually reproduce the OCS envelope.

Conceptually, an API consumer receives:

```json
{
  "ocs": {
    "meta": {
      "...": "..."
    },
    "data": {
      "...": "Health payload"
    }
  }
}
```

The outer structure is managed by Nextcloud.

Successful `GET` endpoints return HTTP 200.

Endpoints that create a resource return HTTP 201.

---

# 8. Content Type

JSON should be the primary documented representation for Health clients.

Clients should request JSON using the standard OCS mechanisms.

---

# 9. Date and Time Format

API requests use RFC3339 timestamps with an explicit timezone or offset.

Example:

```text
2026-08-16T14:30:00+02:00
```

API responses return timestamps canonically as RFC3339 UTC values using `Z`.

Example response timestamp:

```text
2026-08-16T12:30:00Z
```

All time ranges use half-open `[from, to)` semantics:

* `from` is inclusive
* `to` is exclusive

The server must never return ambiguous local timestamps such as:

```text
2026-08-16 14:30
```

without timezone information.

---

# 10. IDs

Database-backed resources expose numeric IDs.

Examples:

```json
{
  "id": 4711
}
```

IDs identify resources but do not grant authorization.

A user may only access resources they own.

---

# 11. Public API Areas

Health API v2 initially contains the following areas:

```text
/modules
/configuration
/entries
/goals
/reminders
/summaries
/statistics
```

---

# 12. Modules

Modules and metric definitions are built into Health.

They are read-only through the API.

## List Modules

```text
GET /api/v2/modules
```

Returns all modules supported by the installed Health version.

Example data:

```json
{
  "modules": [
    {
      "key": "stress",
      "metrics": [
        {
          "key": "stress",
          "valueType": "scale",
          "minimum": 1,
          "maximum": 5,
          "unit": null
        }
      ]
    },
    {
      "key": "sleep",
      "metrics": [
        {
          "key": "sleep_duration",
          "valueType": "duration",
          "unit": "minutes"
        },
        {
          "key": "sleep_recovery",
          "valueType": "scale",
          "minimum": 1,
          "maximum": 5,
          "unit": null
        }
      ]
    }
  ]
}
```

Module definitions are not user-specific.

Localized display labels do not need to form part of the stable API contract.

Stable semantic keys do.

---

# 13. User Configuration

User configuration determines which modules are enabled and where they appear.

Until persistent module configuration is implemented, Stress is initially enabled for a new user.

## Get Configuration

```text
GET /api/v2/configuration
```

The response is private to the authenticated user. It contains the user's profile,
the configuration for every supported metric, and private integration preferences:

```json
{
  "profile": {
    "heightCm": 177.8,
    "heightDisplayUnit": "in",
    "dateOfBirth": "2000-02-29",
    "growthReferenceSex": "female"
  },
  "metrics": {
    "stress": {
      "enabled": true,
      "checkInEnabled": true,
      "checkOutEnabled": false,
      "displayUnit": null
    }
  },
  "searchDailyNotes": false
}
```

---

# 14. Update Configuration

```text
PUT /api/v2/configuration
```

`profile`, `metrics`, and `searchDailyNotes` are optional. Fields not supplied retain their
current value. `null` explicitly clears `height`, `dateOfBirth`, and
`growthReferenceSex`.

```json
{
  "profile": {
    "height": 70,
    "heightUnit": "in",
    "dateOfBirth": "2000-02-29",
    "growthReferenceSex": "female"
  },
  "metrics": {
    "weight": {
      "enabled": true,
      "displayUnit": "kg"
    }
  },
  "searchDailyNotes": true
}
```

`heightUnit` is `cm` or `in`; date of birth is a strict calendar `YYYY-MM-DD`; and
growth reference sex is `female` or `male`. The response has the same shape as
`GET /api/v2/configuration`; height is always returned in canonical centimetres.
The optional date and reference sex are stored for a future verified BMI-for-age
reference and do not produce a diagnosis or an inferred value.

Unknown metric keys, unsupported units, malformed dates, and invalid field types
return a client error. Configuration is always scoped to the authenticated user.
`searchDailyNotes` is a boolean and defaults to `false`; it is the explicit, per-user
permission for the native Nextcloud Unified Search provider to search that user's
plain-text Daily Notes.

---

# 15. Entries

Entries are the central Health resource.

An entry represents exactly one atomic metric value or event.

Canonical entry representation:

```json
{
  "id": 4711,
  "metricKey": "stress",
  "numericValue": 4,
  "optionValue": null,
  "context": "checkin",
  "source": "web",
  "recordedAt": "2026-08-16T06:30:00Z",
  "createdAt": "2026-08-16T06:30:05Z",
  "updatedAt": "2026-08-16T06:30:05Z",
  "note": null
}
```

Exactly one primary value representation should normally be populated.

Journal notes are plain text and have a maximum length of 1000 characters.

---

# 16. Create Entry

```text
POST /api/v2/entries
```

Example numeric metric:

```json
{
  "metricKey": "stress",
  "numericValue": 4,
  "optionValue": null,
  "context": "manual",
  "source": "web",
  "recordedAt": "2026-08-16T14:30:00+02:00",
  "note": null
}
```

Example event metric:

```json
{
  "metricKey": "hydration",
  "numericValue": null,
  "optionValue": "large_glass",
  "context": "manual",
  "source": "web",
  "recordedAt": "2026-08-16T11:15:00+02:00",
  "note": null
}
```

The backend validates the request against the metric definition.

For the implemented Hydration metric, `optionValue` accepts `small_glass`, `large_glass`, `coffee`, `cappuccino`, `espresso`, `double_espresso`, `latte_macchiato`, `cafe_au_lait`, `tea`, or `other`. Coffee and tea variants remain ordinary atomic Hydration entries; they do not introduce a separate metric or endpoint. For the implemented Break metric, `optionValue` accepts `short`, `regular`, `short_walk`, `long_walk`, `mindfulness`, or `fresh_air`.

`source` accepts `web`, `api`, `mobile`, or `notification`. If omitted, it defaults to `api`. Source is informational metadata and never affects authentication, authorization, or ownership.

The response contains the created canonical Entry.

---

# 17. Batch Entry Creation

A single user interaction may create multiple atomic entries.

Examples:

* check-in
* check-out
* sleep recording
* future blood pressure recording

For these cases Health provides a transactional batch endpoint.

```text
POST /api/v2/entries/batch
```

Example check-in:

```json
{
  "context": "checkin",
  "recordedAt": "2026-08-16T08:30:00+02:00",
  "entries": [
    {
      "metricKey": "stress",
      "numericValue": 2,
      "optionValue": null,
      "note": null
    },
    {
      "metricKey": "energy",
      "numericValue": 4,
      "optionValue": null,
      "note": null
    },
    {
      "metricKey": "mood",
      "numericValue": 4,
      "optionValue": null,
      "note": null
    }
  ]
}
```

Example Sleep submission:

```json
{
  "context": "manual",
  "recordedAt": "2026-08-16T07:00:00+02:00",
  "entries": [
    {
      "metricKey": "sleep_duration",
      "numericValue": 435,
      "optionValue": null,
      "note": null
    },
    {
      "metricKey": "sleep_recovery",
      "numericValue": 4,
      "optionValue": null,
      "note": null
    }
  ]
}
```

All entries in a batch share:

* context
* recordedAt

unless a future API version explicitly supports otherwise.

Batch creation is transactional.

Either all entries are created or none are created.

A batch contains at most 20 atomic entries.

Response:

```json
{
  "entries": [
    {
      "...": "canonical entry"
    },
    {
      "...": "canonical entry"
    }
  ]
}
```

---

# 18. Get Entry

```text
GET /api/v2/entries/{id}
```

Returns one canonical Entry.

If the entry does not exist or does not belong to the authenticated user, the endpoint must not disclose information about another user's resource.

---

# 19. Update Entry

```text
PUT /api/v2/entries/{id}
```

The update request contains the complete mutable state of the entry.

Example:

```json
{
  "numericValue": 3,
  "optionValue": null,
  "context": "manual",
  "recordedAt": "2026-08-16T14:00:00+02:00",
  "note": "Project presentation"
}
```

The metric identity of an existing Entry should not be changed through update.

The entry source is creation metadata and is not mutable through update.

To change:

```text
stress
```

into:

```text
energy
```

the client should delete the original entry and create another one.

Response contains the updated canonical Entry.

---

# 20. Delete Entry

```text
DELETE /api/v2/entries/{id}
```

Successful deletion returns:

```text
null
```

in the OCS data field.

The endpoint must be idempotent only if explicitly implemented that way.

The API must not expose whether an inaccessible ID belongs to another user.

---

# 21. List Entries

```text
GET /api/v2/entries
```

Entries are returned newest first.

Default ordering:

```text
recordedAt DESC
id DESC
```

The secondary ID ordering guarantees deterministic pagination when multiple entries share the same timestamp.

---

# 22. Entry Filters

The list endpoint should support:

```text
metricKey
context
from
to
limit
cursor
```

Example:

```text
GET /api/v2/entries?metricKey=stress&from=2026-08-01T00:00:00%2B02:00&limit=50
```

`from` and `to` filter by:

```text
recordedAt
```

not `createdAt`.

The range uses `[from, to)` semantics: entries at `from` are included and entries at `to` are excluded.

---

# 23. Cursor Pagination

Health uses cursor-based pagination for journal entries.

Do not expose database offsets as the preferred pagination mechanism.

Default page size:

```text
50
```

Maximum page size:

```text
200
```

Example first request:

```text
GET /api/v2/entries?limit=50
```

Example data:

```json
{
  "entries": [
    {
      "...": "..."
    }
  ],
  "nextCursor": "opaque-value"
}
```

Next request:

```text
GET /api/v2/entries?limit=50&cursor=opaque-value
```

The cursor must be treated as opaque by clients.

The implementation may internally encode information such as:

```text
recordedAt + id
```

but that representation is not part of the public API contract.

When no additional page exists:

```json
{
  "entries": [],
  "nextCursor": null
}
```

or, if the current page contains final entries:

```json
{
  "entries": [
    {
      "...": "..."
    }
  ],
  "nextCursor": null
}
```

---

# 24. Goals

Goals are personal targets associated with a metric.

Canonical representation:

```json
{
  "id": 81,
  "metricKey": "hydration",
  "goalType": "minimum_count",
  "targetValue": 6,
  "period": "day",
  "enabled": true
}
```

Initial goal types:

```text
minimum_count
minimum_value
maximum_value
completion
```

Initial period:

```text
day
```

---

# 25. List Goals

```text
GET /api/v2/goals
```

Response:

```json
{
  "goals": []
}
```

---

# 26. Create Goal

```text
POST /api/v2/goals
```

Example:

```json
{
  "metricKey": "hydration",
  "goalType": "minimum_count",
  "targetValue": 6,
  "period": "day",
  "enabled": true
}
```

---

# 27. Update Goal

```text
PUT /api/v2/goals/{id}
```

Response contains the resulting Goal.

---

# 28. Delete Goal

```text
DELETE /api/v2/goals/{id}
GET    /api/v2/goals/progress
```

Successful deletion returns:

```text
null
```

---

# 29. Reminders

Reminders describe user-configured Nextcloud notification schedules.

Canonical example:

```json
{
  "id": 31,
  "moduleKey": "hydration",
  "reminderType": "tracking",
  "localTime": "11:00",
  "enabled": true
}
```

The API stores reminder configuration.

Delivery itself is performed through official Nextcloud notification mechanisms.

---

# 30. List Reminders

```text
GET /api/v2/reminders
```

---

# 31. Create Reminder

```text
POST /api/v2/reminders
```

Example:

```json
{
  "moduleKey": "hydration",
  "reminderType": "tracking",
  "localTime": "11:00",
  "enabled": true
}
```

`localTime` represents a local wall-clock time in the user's configured Nextcloud timezone.

---

# 32. Update Reminder

```text
PUT /api/v2/reminders/{id}
```

---

# 33. Delete Reminder

```text
DELETE /api/v2/reminders/{id}
```

---

# 34. Summaries

Summaries are calculated resources.

They are not persisted as separate rows in the MVP.

The API provides a general daily summary endpoint.

```text
GET /api/v2/summaries/days
```

Supported query parameters:

```text
from
to
```

Example:

```text
GET /api/v2/summaries/days?from=2026-08-10&to=2026-08-16
```

---

# 35. Daily Summary

Conceptual daily result:

```json
{
  "date": "2026-08-16",
  "metrics": {
    "stress": {
      "average": 2.8,
      "count": 3
    },
    "hydration": {
      "count": 6
    },
    "break": {
      "count": 3
    },
    "sleep_duration": {
      "average": 435
    }
  },
  "goals": {
    "total": 3,
    "completed": 2
  },
  "checkinCompleted": true,
  "checkoutCompleted": true
}
```

Only values meaningful to a metric should be populated.

Do not fabricate meaningless statistics.

---

# 36. Daily Summary Ranges

The daily-summary resource remains available for clients that need daily goal and journal summaries:

```text
GET /api/v2/summaries/days?from=&to=
```

The `from` and `to` parameters define the requested period using `[from, to)` semantics. It does not define a separate Overview screen in Health v3.

---

# 37. Statistics

Health v3 Statistics returns day-level, owner-scoped data for one or more requested chartable metrics.

Endpoint:

```text
GET /api/v2/statistics
```

Supported query parameters:

```text
period
metrics
```

`period` is one of:

```text
this_week
last_week
last_7_days
last_30_days
this_month
last_month
this_year
last_year
```

When omitted, `period` defaults to `last_30_days`.

`metrics` is an optional comma-separated list of stable metric identifiers, for example:

```text
GET /api/v2/statistics?period=last_30_days&metrics=stress,energy,hydration
```

When omitted, `metrics` defaults to the authenticated user's enabled Journal metrics. An empty string requests no metrics. Unknown or malformed metric keys and unsupported periods return `400 Bad Request`.

The service resolves the selected period using the authenticated user's Nextcloud timezone. All returned dates represent local calendar days and the underlying query range is `[from, to)`. The endpoint never accepts a user ID and all returned data is scoped to the authenticated user.

---

# 38. Statistics Response

The response returns canonical values and contains one item for every requested metric, including metrics with no recorded data in the selected period.

```json
{
  "period": "last_30_days",
  "from": "2026-07-29",
  "to": "2026-08-28",
  "metrics": [
    {
      "metricKey": "job_satisfaction",
      "category": "daily_value",
      "valueType": "scale",
      "canonicalUnit": null,
      "minimum": 1,
      "maximum": 5,
      "series": [
        {
          "date": "2026-07-29",
          "value": 2.5,
          "subseries": null
        },
        {
          "date": "2026-07-30",
          "value": null,
          "subseries": null
        }
      ],
      "summary": {
        "average": 2.8,
        "minimum": 1.5,
        "maximum": 5,
        "count": 26,
        "activeDays": 18,
        "subseries": null
      },
      "goals": [
        {
          "goalId": 42,
          "targetKey": "job_satisfaction",
          "metricKey": "job_satisfaction",
          "kind": "period_value",
          "seriesKey": "value",
          "comparator": "gte",
          "targetValue": 4,
          "options": null,
          "effectiveFrom": "2026-08-01",
          "effectiveTo": null
        }
      ]
    },
    {
      "metricKey": "hydration",
      "category": "journal",
      "valueType": "event",
      "canonicalUnit": null,
      "minimum": null,
      "maximum": null,
      "series": [
        {
          "date": "2026-07-29",
          "value": 6,
          "subseries": {
            "water": 4,
            "coffee": 1,
            "tea": 1,
            "other": 0
          }
        }
      ],
      "summary": {
        "average": 4.7,
        "minimum": 0,
        "maximum": 9,
        "count": 142,
        "activeDays": 24,
        "subseries": {
          "water": {
            "average": 3.3,
            "minimum": 0,
            "maximum": 6,
            "count": 98,
            "activeDays": 20,
            "subseries": null
          }
        }
      },
      "goals": []
    }
  ]
}
```

Each metric object contains the metric identity, its category and value type, the canonical unit when applicable, metric `minimum` and `maximum` when defined, daily `series`, `summary`, and the goal-revision segments that can be represented as chart thresholds. `summary.count` is the number of source records, not the number of day buckets; `activeDays` is the number of day buckets with a recorded numeric point or a non-zero event total. Event metrics and blood pressure provide summaries for their individual series through `summary.subseries`.

Every daily point has a `subseries` field. It is `null` for ordinary numeric metrics. It is a map for event metrics and blood pressure, where one logical metric has multiple chart series. Hydration uses `water`, `coffee`, `tea`, and `other`; Break uses its configured event categories; blood pressure uses `systolic` and `diastolic`.

---

# 39. Daily Series and Goal Semantics

Numeric scale and measurement values are aggregated as an arithmetic mean for each local day. A missing numeric day is represented by `value: null` and is excluded from numeric summaries.

Event metrics have a valid daily count of zero. Their series therefore contains zero-valued local days, and their average, minimum, and maximum include every day in the selected period.

Applicable daily goal revisions are returned as separate `goals` segments with an inclusive `effectiveFrom` and optional inclusive `effectiveTo` local date. Clients must not apply a current target retrospectively outside its effective segment. A goal identifies its `metricKey`, `kind`, `seriesKey`, and target `options` when applicable; `seriesKey` may be `value`, an event subseries such as Water or Mindfulness, or Break's `total`. Incompatible goal types are omitted.

Statistics response definitions are explicit in OpenAPI. Avoid undocumented dynamic response structures.

---

# 40. No Cross-Metric Analysis in API v2 MVP

The MVP does not provide endpoints such as:

```text
/correlations
/insights
/patterns
/recommendations
```

The statistics API describes recorded data.

It does not interpret relationships between metrics.

A future API may add comparison features without changing the basic Entry model.

---

# 41. Capabilities

Health exposes feature discovery through Nextcloud Capabilities.

Conceptually:

```json
{
  "health": {
    "apiVersions": [
      "2"
    ],
    "features": [
      "entries",
      "batchEntries",
      "goals",
      "reminders",
      "dailySummaries",
      "statistics"
    ],
    "modules": [
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

External clients should use capabilities to detect available functionality where appropriate.

---

# 42. Validation

All API input is validated server-side.

Examples of invalid requests include:

```text
stress = 99
```

```text
hydration = "bucket"
```

```text
sleep_duration = -120
```

```text
unknown metric key
```

```text
invalid context
```

```text
invalid timestamp
```

The Vue frontend may provide client-side validation for UX purposes, but it is not authoritative.

---

# 43. Error Handling

OCS endpoints must use official OCS exception types.

Typical semantics include:

* bad request for malformed or invalid input
* not found for unavailable owned resources
* forbidden where a request is authenticated but explicitly not permitted

Do not leak:

* database errors
* SQL
* stack traces
* internal class names
* another user's data existence

Error messages should be safe and useful to API consumers.

---

# 44. Cross-user Resource Protection

For an endpoint such as:

```text
GET /entries/4711
```

the implementation must query using both:

```text
authenticated user
+
entry ID
```

Do not:

1. retrieve the entry globally by ID
2. return it
3. rely on the frontend not to request other IDs

The same rule applies to:

* goals
* reminders
* user configuration
* statistics
* summaries

---

# 45. API Tests

Every endpoint requires API tests.

Tests are the primary automated testing priority for the MVP.

Minimum coverage includes:

## Authentication

Unauthenticated requests fail.

## Successful request

Valid requests return the documented response.

## Validation

Invalid values are rejected.

## Ownership

User A cannot access User B's resources.

## Modification

Create, update and delete operations behave as documented.

## Pagination

Cursor pagination:

* does not duplicate entries
* does not skip entries under stable data
* preserves deterministic ordering
* respects the maximum limit

## OpenAPI

The resulting implementation remains extractable by the official OpenAPI tooling.

---

# 46. API Test Users

Integration/API tests should use at least two distinct users.

Conceptually:

```text
health-user-a
health-user-b
```

Cross-user tests must be explicit.

Example:

1. User A creates Entry 4711.
2. User B requests Entry 4711.
3. User B must not receive User A's data.
4. User B must not update Entry 4711.
5. User B must not delete Entry 4711.

---

# 47. OpenAPI Response Definitions

Reusable public response structures should be centrally defined in:

```text
lib/ResponseDefinitions.php
```

where appropriate.

Examples may include:

```text
Entry
Module
MetricDefinition
UserModuleConfiguration
Goal
Reminder
DailySummary
NumericStatistics
EventStatistics
PaginatedEntries
```

Do not duplicate incompatible response shapes across controllers.

---

# 48. OpenAPI Documentation Requirements

Every endpoint must document:

* purpose
* parameters
* request fields
* response structure
* HTTP status codes
* possible documented errors

Every API parameter must have an explicit type.

Every response must have an explicit type.

Public API methods without sufficient information for OpenAPI extraction are incomplete.

---

# 49. API Evolution

Health API v2 should evolve additively where possible.

Safe examples:

* adding a new endpoint
* adding a new built-in metric
* adding an optional response field
* adding a new capability

Potentially breaking examples:

* renaming `metricKey`
* changing the meaning of an existing metric
* changing numeric units
* removing response fields
* changing stable enum values
* changing endpoint paths

Breaking changes require careful review and may require Health API v3.

---

# 50. API Implementation Order

For every new feature, implement in this order:

```text
1. Define API contract
2. Define response types
3. Implement domain/service logic
4. Implement persistence
5. Implement OCS controller
6. Add API tests
7. Verify OpenAPI extraction
8. Implement frontend API client
9. Implement Vue UI
```

Do not begin with the frontend and invent the API afterward.

---

# 51. MVP Endpoint Overview

Initial Health API v2:

```text
GET    /api/v2/modules

GET    /api/v2/configuration
PUT    /api/v2/configuration

GET    /api/v2/entries
POST   /api/v2/entries
POST   /api/v2/entries/batch
GET    /api/v2/entries/{id}
PUT    /api/v2/entries/{id}
DELETE /api/v2/entries/{id}

GET    /api/v2/goals
POST   /api/v2/goals
PUT    /api/v2/goals/{id}
DELETE /api/v2/goals/{id}

GET    /api/v2/reminders
POST   /api/v2/reminders
PUT    /api/v2/reminders/{id}
DELETE /api/v2/reminders/{id}

GET    /api/v2/summaries/days

GET    /api/v2/statistics

GET    /api/v2/daily-notes/{date}
PUT    /api/v2/daily-notes/{date}
```

This is the planned MVP API surface.

Do not add additional public endpoints unless a concrete requirement cannot be expressed through this model.

## Daily notes

`date` is a strict local `YYYY-MM-DD` key. `GET` returns `{ date, content, createdAt, updatedAt }`; when no note exists, `content`, `createdAt`, and `updatedAt` are explicitly `null`. `PUT` accepts `{ content }`, where content is plain text of at most 2000 characters, and returns the same representation. Nextcloud rich-content UI controls may be used only when they preserve this plain-text contract; HTML is never stored.

## Profile configuration

The Configuration response profile contains canonical `heightCm`, the display preference `heightDisplayUnit`, optional `dateOfBirth` as strict `YYYY-MM-DD`, and optional `growthReferenceSex` (`female` or `male`). Profile updates remain owner-scoped and use the existing configuration endpoint. Height is stored canonically in centimetres; date of birth and reference sex are used only for a future verified BMI-for-age calculation and must not infer medical information.

## Native search preference

`searchDailyNotes` is an owner-scoped integration preference in the Configuration response. It defaults to `false`, including for existing users after an upgrade. When enabled, the native Nextcloud Unified Search provider searches only the requesting user's Daily Notes; it never exposes notes to another user. Search does not add a Health data endpoint or change the Daily Note plain-text API contract.

## Goals v2

`GET /goals` returns owner-scoped active and paused logical goals plus the public, non-personal target registry; retired identities are excluded from goal management. `POST /goals` creates an identity with `targetKey`, `period`, `comparator` (`gte` or `lte`), canonical `targetValue`, and `remindersEnabled`. `PUT /goals/{id}` updates the owner-scoped goal; `active: false` pauses without retiring it and `active: true` resumes that same identity. Value/direction changes create or update the current local-period revision, while target/period changes retire the old identity and create a new one. `DELETE /goals/{id}` retires rather than deletes history. `GET /goals/progress?period=day|week|month|long_term&date=YYYY-MM-DD` returns derived owner-scoped progress; finite dates are interpreted in the user's Nextcloud timezone and future periods are rejected. Job Satisfaction uses the existing daily-value endpoint with `metricKey: "job_satisfaction"`, integer numeric values 1–5, and `unit: null`.
