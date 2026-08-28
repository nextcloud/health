# Health v2 – Data Model

## 1. Purpose

Health stores personal health and wellbeing journal data using a small, typed and extensible data model.

The data model must support:

* simple scalar measurements
* event-based tracking
* daily habits
* check-in and check-out
* goals
* reminders
* statistics
* future health metrics

The model must remain understandable and must not become a generic EAV or arbitrary JSON storage system.

---

# 2. Design Principles

The Health data model follows these principles:

1. One journal entry represents one atomic measurement or event.
2. Built-in metrics have stable semantic identifiers.
3. Metric definitions are defined by application code.
4. User-specific configuration is stored separately from metric definitions.
5. Entries always belong to exactly one authenticated Nextcloud user.
6. Statistics operate on typed database columns, not arbitrary JSON payloads.
7. Composite UI modules may produce multiple atomic metric entries.
8. All schema changes use official Nextcloud migrations.
9. Database access uses official Nextcloud database APIs, entities and mappers.
10. The schema must remain portable across databases supported by Nextcloud.

---

# 3. Modules vs Metrics

Health distinguishes between a **module** and a **metric**.

## Module

A module is a user-facing tracking feature.

Initial modules include:

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

Modules are primarily a product and UI concept.

A module may contain one or more metrics.

---

## Metric

A metric represents one atomic measurable value or event.

Examples:

```text
stress
energy
mood
hydration
break
movement
sleep_duration
sleep_recovery
weight
```

Metric identifiers are part of the public API and must remain stable after release.

---

# 4. Why Metrics Are Atomic

Health should avoid storing complex arbitrary structures such as:

```json
{
  "metric": "sleep",
  "value": {
    "duration": 420,
    "recovery": 4,
    "quality": "good"
  }
}
```

This makes:

* validation
* filtering
* aggregation
* database queries
* API documentation
* statistics

more complicated.

Instead, a sleep recording may create:

```text
sleep_duration = 420
sleep_recovery = 4
```

with the same `recordedAt` timestamp.

The frontend may still present this as one Sleep form.

---

# 5. Built-in Modules and Metrics

## Stress

Module:

```text
stress
```

Metric:

```text
stress
```

Type:

```text
scale
```

Value:

```text
1–5
```

Aggregation:

```text
average
```

---

## Energy

Module:

```text
energy
```

Metric:

```text
energy
```

Type:

```text
scale
```

Value:

```text
1–5
```

Aggregation:

```text
average
```

---

## Mood

Module:

```text
mood
```

Metric:

```text
mood
```

Type:

```text
scale
```

Value:

```text
1–5
```

Aggregation:

```text
average
```

---

## Hydration

Module:

```text
hydration
```

Metric:

```text
hydration
```

Type:

```text
event
```

Allowed options:

```text
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

Aggregation:

```text
count
```

The MVP intentionally does not require exact milliliter tracking.

The selected option describes the type of hydration event.

---

## Break

Module:

```text
break
```

Metric:

```text
break
```

Type:

```text
event
```

Initial options:

```text
short
regular
short_walk
long_walk
mindfulness
fresh_air
```

Aggregation:

```text
count
```

---

## Movement

Module:

```text
movement
```

Initial metrics:

```text
movement
steps
```

`movement` is event-based.

Initial options:

```text
short_walk
active_break
other
```

`steps` is numeric and may be entered manually.

Future automatic device synchronization must reuse the same metric identifier.

---

## Sleep

Module:

```text
sleep
```

Metrics:

```text
sleep_duration
sleep_recovery
```

### sleep_duration

Type:

```text
duration
```

Stored unit:

```text
minutes
```

Aggregation:

```text
average
```

### sleep_recovery

Type:

```text
scale
```

Range:

```text
1–5
```

Aggregation:

```text
average
```

Both values may be submitted together through the UI.

They remain separate atomic entries internally.

---

## Weight

Module:

```text
weight
```

Metric:

```text
weight
```

Type:

```text
number
```

Canonical storage unit:

```text
kg
```

Aggregation:

```text
latest
```

The API may support alternative display units later.

Stored canonical values must remain stable regardless of display preference.

---

# 6. Metric Definitions

Built-in metric definitions are application definitions and are not database records.

Conceptually a definition contains:

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

Metric definitions are the source of truth for backend validation.

The frontend may retrieve definitions through the API but must not define authoritative validation rules independently.

---

# 7. Entry

The central persistence object is `Entry`.

Conceptual fields:

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

---

# 8. Entry Value

Exactly one primary value representation should normally be used per entry.

## numericValue

Used for:

* scales
* numeric measurements
* durations
* counts where recorded as explicit measurements

Examples:

```text
stress = 4
weight = 82.4
sleep_duration = 435
steps = 7200
```

The database column must support decimal values.

Integer-style metrics are validated as integers at the domain layer where required.

---

## optionValue

Used for event variants.

Examples:

```text
hydration = large_glass
break = short
movement = short_walk
```

`optionValue` must match an option defined by the metric definition.

Arbitrary client-defined option values are not accepted for built-in metrics.

---

# 9. No Generic JSON Value

The primary metric value must not be stored as arbitrary JSON.

JSON should not become an escape hatch for adding undocumented metric structures.

If a future feature genuinely requires structured data, its schema must be explicitly designed and documented.

---

# 10. Entry Context

Each entry may have a context.

Initial contexts:

```text
manual
checkin
checkout
reminder
```

Example:

```text
metricKey: stress
numericValue: 4
context: checkin
```

Context is metadata.

It does not change the semantic meaning of the metric itself.

## Entry Source

Each entry stores informational source metadata describing the client channel that created it.

Initial supported values are:

```text
web
api
mobile
notification
```

`source` is never used for authentication, authorization, or ownership. The web frontend explicitly sends `web`; API creation defaults to `api` when the field is omitted.

---

# 11. Notes

An entry may contain an optional short personal note.

Example:

```text
Large project deadline today
```

Notes are:

* private
* optional
* plain text
* not analyzed by Health in the MVP

Notes must have a defined maximum length.

Maximum journal note length:

```text
1000 characters
```

---

# 12. Timestamps

Every entry has:

```text
recordedAt
createdAt
updatedAt
```

## recordedAt

The time the measurement or event actually occurred.

This may be supplied by the user.

## createdAt

The time Health created the database record.

## updatedAt

The time the record was last modified.

Example:

```text
recordedAt = 2026-08-16T14:00
createdAt  = 2026-08-16T19:02
```

Health must never assume that `createdAt` represents when the health event happened.

---

# 13. Timezone

Persistent timestamps must be stored in an unambiguous canonical representation.

The API accepts explicit RFC3339 date-time values and returns timestamps canonically as RFC3339 UTC values using `Z`.

Time ranges use `[from, to)` semantics. `from` is inclusive and `to` is exclusive.

The frontend displays dates and times according to the user's Nextcloud timezone.

Day-based aggregation must use the user's timezone.

This is important because:

```text
2026-08-16 23:30 UTC
```

may belong to a different calendar day for the user.

---

# 14. User Ownership

Every persistent personal Health object contains a user owner.

This includes:

* entries
* metric configuration
* goals
* reminders

Ownership is determined by the authenticated Nextcloud account.

The API never allows a client to select an arbitrary owner.

A submitted body such as:

```json
{
  "userId": "another-user"
}
```

must never grant access to that user's Health data.

---

# 15. Entry Table

Conceptual table:

```text
health_entries
```

Initial fields:

```text
id
user_id
metric_key
numeric_value
option_value
context
source
recorded_at
created_at
updated_at
note
```

Recommended indexes include combinations supporting:

```text
user_id + recorded_at
user_id + metric_key + recorded_at
```

Exact database types and index definitions belong to the migration implementation.

---

# 16. User Metric Configuration

Table:

```text
health_user_metrics
```

Purpose:

Stores how the individual user has configured built-in modules and metrics.

Conceptual fields:

```text
id
user_id
module_key
enabled
show_in_quick_entry
include_in_checkin
include_in_checkout
display_order
created_at
updated_at
```

The configuration references stable application-defined module identifiers.

It does not duplicate the metric definition itself.

Until persistent module configuration is implemented, the Stress module is initially enabled for a new user.

---

# 17. Module Configuration

Configuration occurs primarily at module level.

Example:

```text
module: sleep
enabled: true
includeInCheckin: true
```

The Sleep module may then collect:

```text
sleep_duration
sleep_recovery
```

This avoids requiring users to understand internal metric identifiers.

---

# 18. Goals

Table:

```text
health_goals
```

A goal defines a personal target.

Conceptual fields:

```text
id
user_id
metric_key
goal_type
target_value
period
enabled
created_at
updated_at
```

Initial goal types should remain deliberately limited.

Examples:

```text
minimum_count
maximum_value
minimum_value
completion
```

Initial period:

```text
day
```

Examples:

Hydration:

```text
metricKey: hydration
goalType: minimum_count
targetValue: 6
period: day
```

Break:

```text
metricKey: break
goalType: minimum_count
targetValue: 3
period: day
```

Avoid building a generic rules engine for goals.

---

# 19. Goal Evaluation

Goal definitions are persistent data.

Goal results are calculated data.

Do not store daily goal completion records unless a future performance requirement demonstrates a need for them.

Example:

```text
hydration goal = 6
events today = 5

completion = 83.3%
```

This should initially be calculated from entries.

---

# 20. Reminders

Table:

```text
health_reminders
```

Conceptual fields:

```text
id
user_id
module_key
reminder_type
local_time
enabled
created_at
updated_at
```

Examples:

```text
moduleKey: stress
reminderType: checkin
localTime: 08:30
```

```text
moduleKey: hydration
reminderType: tracking
localTime: 11:00
```

Nextcloud Notifications are used to deliver reminders.

The reminder record describes user preference and schedule.

---

# 21. Reminder Scope

The MVP uses simple scheduled reminders.

Do not initially implement complex rules such as:

```text
every 47 minutes unless the user recently logged water
```

or:

```text
only when the user appears active
```

Initial reminders are explicit user-configured times.

---

# 22. Journal

The journal is not a separate database entity.

The journal is a chronological representation of entries.

Example:

```text
08:15  Energy       4 / 5
08:15  Stress       2 / 5
10:30  Hydration    large_glass
12:10  Break        regular
15:45  Stress       4 / 5
17:20  Energy       3 / 5
```

This is produced by querying `health_entries`.

Do not create a separate journal table.

---

# 23. Check-in and Check-out

Check-ins and check-outs are not separate persistence models.

They are groups of ordinary metric entries with context:

```text
checkin
```

or:

```text
checkout
```

Example morning check-in:

```text
energy = 4
stress = 2
mood = 4
```

creates three atomic entries.

All three receive:

```text
context = checkin
```

and the same `recordedAt`.

This keeps statistics consistent regardless of how a value was entered.

---

# 24. Atomic Transactions

When a single user action creates multiple entries, such as a check-in or Sleep submission, the operation should be transactional.

Either all related entries are persisted successfully or none are.

This prevents partial states such as:

```text
sleep_duration saved
sleep_recovery failed
```

---

# 25. Statistics

Statistics are derived from entries.

They are not stored separately in the MVP.

Examples:

```text
average stress
average energy
latest weight
hydration events per day
break events per day
average sleep duration
```

No statistics cache table should initially be introduced.

Optimize only when actual data or performance measurements justify it.

---

# 26. Daily Summaries

Daily summaries are derived data.

They may include:

```text
date
trackedMetrics
goalCompletion
averages
eventCounts
checkinCompleted
checkoutCompleted
```

They are calculated by services from entries and goals.

Do not initially persist summary rows.

---

# 27. Data Deletion

Deleting an entry removes the user's recorded journal entry.

Deleting or disabling a module does not automatically delete historical entries.

Example:

A user disables Weight.

Historical weight records remain available unless explicitly deleted.

This prevents configuration changes from unexpectedly destroying personal data.

---

# 28. Metric Lifecycle

Metric identifiers must not be reused for different semantics.

If a built-in metric is deprecated:

1. keep its identifier stable
2. preserve existing historical records
3. remove it from new configuration if necessary
4. document migration behavior

Never redefine an existing key with a different meaning.

---

# 29. Unknown Historical Metrics

The application should be able to safely display or export historical entries whose metric definition has later been deprecated.

Unknown metric keys must not crash the journal.

They may be shown using a generic fallback representation.

---

# 30. Future Metrics

The architecture should make metrics such as these possible later:

```text
blood_pressure_systolic
blood_pressure_diastolic
pulse
body_fat
blood_glucose
temperature
oxygen_saturation
```

Adding a simple numeric metric should normally require:

* metric definition
* validation
* presentation
* API documentation/tests

It should not require a new database table.

---

# 31. Entry Sources

Entry origin is explicit metadata in the `source` field and is not encoded into metric identifiers. The initial closed set is `web`, `api`, `mobile`, and `notification`; additional values require an explicit API evolution.

---

# 32. Database Migrations

All schema creation and modification must use official Nextcloud migration classes.

Do not use deprecated database schema files.

Do not manually execute schema-changing SQL during normal application execution.

Migration files belong in:

```text
lib/Migration/
```

---

# 33. Persistence Architecture

Each major persistent entity has a dedicated mapper.

Conceptually:

```text
Entry
EntryMapper

UserMetric
UserMetricMapper

Goal
GoalMapper

Reminder
ReminderMapper
```

Mappers must always support safe user-scoped access.

---

# 34. Data Access Rules

Unsafe patterns:

```text
findAllEntries()
findById($id)
delete($id)
```

for user-facing operations.

Preferred conceptual patterns:

```text
findForUser($userId, ...)
findForUserById($userId, $entryId)
deleteForUser($userId, $entryId)
```

The service layer obtains the current user identity from trusted Nextcloud context.

---

# 35. Initial Tables

The MVP should initially require only four application tables:

```text
health_entries
health_user_metrics
health_goals
health_reminders
```

Do not introduce additional tables without a concrete requirement.

---

# 36. No Premature Caching

Do not create:

```text
health_daily_statistics
health_weekly_statistics
health_monthly_statistics
```

in the MVP.

Statistics should first be calculated directly from indexed journal data.

Caching may be introduced later if measured performance requires it.

---

# 37. Data Model Priorities

When implementation choices conflict, prioritize:

1. privacy and user isolation
2. clear semantics
3. stable public metric identifiers
4. simple querying
5. database portability
6. API usability
7. extensibility
8. storage optimization

Do not sacrifice understandable data for theoretical flexibility.

## Daily notes

`health_daily_notes` stores one private plain-text note per user and local date. Its unique `(user_id, local_date)` constraint prevents duplicate daily notes. It has `content`, `created_at`, and `updated_at` fields; content is limited to 2000 characters and is not journal-entry data.

## Profile and BMI

`health_user_settings` stores optional canonical height in centimetres, a height display preference, full `date_of_birth` in `YYYY-MM-DD` form, and optional `growth_reference_sex` (`female` or `male`). These fields are owner-scoped configuration, not journal measurements. BMI is derived from canonical weight and height for the daily value date and is never persisted independently. BMI-for-age percentile or z-score values are not returned until a verified bundled WHO reference dataset is explicitly added; Health must not approximate or fabricate them.

## Goals and reminder state

`health_goals` stores the owner-scoped logical identity (`user_id`, stable `target_key`, period), active/reminder flags, retirement timestamp, and audit timestamps. `health_goal_revisions` stores comparator and canonical target values with inclusive local-date effective ranges; a revision changes current and future periods without rewriting history. `health_goal_reminder_state` stores only per-goal/period deduplication metadata (time, count, reason), never a Health value. Progress itself is derived and is not stored. `job_satisfaction` is an atomic `health_daily_values` metric with integer canonical values 1–5 and no unit.
