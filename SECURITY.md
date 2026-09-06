# Health v2 – Security and Privacy Architecture

## 1. Purpose

Health stores sensitive personal health and wellbeing information.

Security and privacy are therefore architectural requirements, not optional features.

This document defines mandatory security rules for:

* backend implementation
* OCS API
* database access
* frontend behavior
* logging
* notifications
* external clients
* AI-assisted development

---

# 2. Security Principle

Health follows a simple rule:

> A user may access only their own Health data.

This rule applies to:

* entries
* notes
* goals
* reminders
* configuration
* summaries
* statistics

There is no Health-specific mechanism for accessing another user's personal Health data.

---

# 3. Privacy Boundary

Health provides application-level privacy.

Through the Health application and Health API:

* users only access their own data
* administrators cannot browse other users' Health data
* managers cannot browse employee Health data
* other applications do not automatically receive Health data
* there is no organization-wide health dashboard

However, Health does not implement additional application-level encryption in the MVP.

Therefore:

> A Nextcloud server administrator or database administrator with direct infrastructure or database access may technically be able to access stored Health data.

Health must not claim that data is cryptographically hidden from the Nextcloud server operator.

---

# 4. Authentication

Health uses only official Nextcloud authentication mechanisms.

Health must not implement:

* passwords
* custom login tokens
* custom sessions
* custom OAuth-like mechanisms

External clients use authentication mechanisms supported by Nextcloud.

Health must never store:

* Nextcloud passwords
* app passwords
* authentication tokens
* OIDC tokens

in Health application tables.

---

# 5. OCS API Security

All Health data APIs use `OCSController`.

Normal Health users require:

```php
#[NoAdminRequired]
```

because Health is intended for regular Nextcloud users.

Health API endpoints must not use:

```php
#[PublicPage]
```

Health API endpoints must not use:

```php
#[NoTwoFactorRequired]
```

Health API endpoints must not use:

```php
#[NoCSRFRequired]
```

unless a future, explicitly reviewed use case proves this absolutely necessary.

Health API endpoints must not use:

```php
#[CORS]
```

in the MVP.

---

# 6. CSRF Protection

Do not disable Nextcloud CSRF protection for Health data endpoints.

The Nextcloud web frontend uses the standard Nextcloud request mechanisms.

External OCS clients must use the appropriate OCS request headers and authentication.

Do not weaken CSRF protection merely to make an external client easier to implement.

---

# 7. CORS

Health deliberately does not allow arbitrary websites to access Health API data directly through browser JavaScript.

Mobile applications, desktop clients and command-line tools do not require unrestricted browser CORS.

Do not enable wildcard origins such as:

```text
Access-Control-Allow-Origin: *
```

Do not enable credentialed cross-origin browser access without a dedicated security review.

---

# 8. Page Controller

The controller used only to render the main Health application page may follow official Nextcloud template-controller conventions.

A template-only route may use security attributes appropriate for rendering the application shell.

This exception applies only to the HTML application entry point.

It must never be used as justification for weakening security on Health data APIs.

---

# 9. User Identity

The authenticated Nextcloud session is the authoritative source of user identity.

Clients must never select the owner of a Health resource.

Never accept authorization based on request fields such as:

```json
{
  "userId": "alice"
}
```

A client-supplied user ID must never grant access to data.

---

# 10. User-scoped Queries

Every query involving personal Health data must include the authenticated user's ID.

Unsafe:

```text
findById($entryId)
```

Preferred:

```text
findForUserById($userId, $entryId)
```

Unsafe:

```text
findEntries()
```

Preferred:

```text
findEntriesForUser($userId, ...)
```

Unsafe:

```text
deleteById($entryId)
```

Preferred:

```text
deleteForUser($userId, $entryId)
```

Ownership must be enforced in backend persistence/service logic.

It must never depend only on frontend behavior.

---

# 11. IDOR Protection

Numeric resource IDs are identifiers, not authorization credentials.

Knowing:

```text
entryId = 4711
```

must not allow another user to access Entry 4711.

This applies to:

* GET
* PUT
* DELETE

operations.

The same principle applies to:

* goals
* reminders
* configuration resources

---

# 12. Cross-user Information Leakage

If User B requests a resource belonging to User A, the response must not expose:

* the resource content
* its metric
* whether the resource exists for User A
* timestamps
* notes
* ownership information

Errors should reveal only what the requesting user is allowed to know.

---

# 13. Administrator Accounts

A Nextcloud administrator may use Health for their own personal journal.

Administrator status does not grant access to Health data belonging to other users through Health.

There must be no API endpoint such as:

```text
GET /admin/users/{userId}/health
```

There must be no administrator Health-data browser.

---

# 14. Input Validation

All input is validated server-side.

The frontend may validate values for usability, but frontend validation is not trusted for security or correctness.

Examples:

```text
stress = 4
```

is valid.

```text
stress = 999
```

is invalid.

```text
hydration = large_glass
```

is valid.

```text
hydration = arbitrary_client_value
```

is invalid.

All enums, ranges, timestamps, IDs and lengths must be validated.

---

# 15. Batch Requests

Batch entry creation must be bounded.

Clients must not be allowed to submit arbitrarily large batches.

The maximum batch size is 20 atomic entries and must be enforced by the API.

Each individual entry in a batch must be validated.

A failed validation causes the complete transactional batch to fail.

---

# 16. SQL and Database Access

Use official Nextcloud database APIs.

Use:

* entities
* mappers
* QueryBuilder
* parameterized queries

Do not concatenate untrusted values into SQL.

Do not construct SQL fragments directly from:

* metric keys
* cursor values
* sort parameters
* request parameters

without explicit validation.

---

# 17. Dynamic Sorting and Filtering

Client-controlled values used to influence queries must be mapped to known server-side values.

Unsafe concept:

```php
ORDER BY $requestValue
```

Preferred concept:

```text
requested "recordedAt"
→ predefined recorded_at column
```

Unknown sort or filter identifiers must be rejected.

---

# 18. Cursor Security

Pagination cursors are opaque client values.

Clients must not control arbitrary SQL through a cursor.

Cursor contents must be:

* validated
* safely decoded
* bounded
* treated as untrusted input

Invalid cursors return a safe client error.

A cursor must never contain sensitive Health values.

---

# 19. Notes

Journal notes are plain text in the MVP.

Journal notes have a maximum length of 1000 characters.

Notes do not support arbitrary HTML.

Example:

```text
Project deadline today
```

must be treated as text.

Do not render note content through unsafe HTML mechanisms.

Do not use unescaped user content with:

```text
v-html
innerHTML
```

unless a future feature introduces a reviewed sanitization mechanism.

---

# 20. XSS Protection

All user-generated content must be rendered safely.

This includes:

* notes
* future custom labels
* imported content
* API-provided strings

Prefer Vue's normal escaped text rendering.

Do not manually build HTML using user-controlled strings.

---

# 21. External Content

The MVP must not load Health-related content from external services.

Do not load:

* analytics scripts
* advertising scripts
* remote fonts
* remote JavaScript
* remote tracking pixels
* third-party wellness content

from external origins.

Frontend dependencies must be bundled with the application.

Chart.js must be bundled locally and must not be loaded from a CDN.

---

# 22. No Tracking or Telemetry

Health does not send personal Health usage or measurements to third parties.

Do not implement external telemetry containing:

* metric values
* journal entries
* notes
* stress levels
* mood
* sleep
* weight
* hydration
* movement
* goal completion

Any future telemetry feature requires explicit product and privacy review.

---

# 23. Logging

Health uses the official Nextcloud PSR-3 logging infrastructure.

Logger instances should normally be provided through dependency injection.

Logs are for operational and technical information.

Logs are not a secondary Health-data store.

---

# 24. Health Data Must Not Be Logged

Never log:

* metric values
* journal notes
* sleep values
* weight values
* mood values
* stress values
* hydration events
* movement values
* check-in contents
* check-out contents
* API request bodies
* API response bodies containing Health data

Bad:

```text
User steffen recorded stress level 5
```

Bad:

```text
POST /entries payload: {...}
```

Better:

```text
Failed to create Health entry
```

where necessary accompanied only by safe operational identifiers.

---

# 25. Authentication Data Must Not Be Logged

Never log:

* passwords
* app passwords
* authorization headers
* cookies
* bearer tokens
* OIDC tokens
* session tokens

Do not add these values to exception context.

---

# 26. Safe Logging Context

Where operationally necessary, logs may contain non-health technical information such as:

* internal resource ID
* controller operation
* exception class
* processing duration

User IDs should only be logged when genuinely needed for troubleshooting.

Avoid combining an identifiable user with personal health information in logs.

---

# 27. Exceptions

Exceptions may be logged through the standard Nextcloud logger.

Do not attach complete:

* Entry objects
* request objects
* request bodies
* Health response payloads

to logging context.

Exception messages returned to API clients must not expose internal implementation details.

---

# 28. API Errors

API errors must not expose:

* SQL
* database table names
* filesystem paths
* stack traces
* PHP class internals
* secrets
* another user's resource information

Development environments may expose additional debugging information through Nextcloud configuration, but production API responses remain safe.

---

# 29. Notifications

Health reminders use official Nextcloud Notifications.

Notifications must be privacy-preserving.

A Health notification may say:

```text
Time for your Health check-in
```

or:

```text
You scheduled a Health reminder
```

A Health notification must not say:

```text
Your stress level was 5 yesterday
```

or:

```text
You only drank 3 glasses today
```

Goal reminder notifications may identify the associated metric or topic using its localized label and associated icon. This deliberate exception makes reminders useful, for example by showing a Water reminder with the Water icon.

Goal reminder notifications must not include measurement values, goal target values, current progress or completion, remaining amounts, percentages, journal text or notes, detailed history, or medical interpretation. Health values must not be included in notification titles or messages.

This is important because notifications may be visible outside the Health application.

---

# 30. Reminder Processing

Background jobs responsible for reminders should process only the information required to deliver the reminder.

They should not unnecessarily load journal history or statistics.

Reminder jobs must not produce Health-data logs.

---

# 31. Statistics

Statistics are calculated only for the authenticated user.

There is no cross-user statistics service.

Do not implement a generic service accepting arbitrary:

```text
userId
```

for statistics from public API calls.

The authenticated context supplies the user identity.

---

# 32. Capabilities

Nextcloud Capabilities may expose:

* Health installation status
* API versions
* supported modules
* supported features

Capabilities must not expose:

* enabled modules of another user
* entries
* goals
* reminders
* journal activity
* whether an individual user uses Health

Capabilities describe application functionality, not personal Health state.

---

# 33. Data Export

If Health later provides an export feature, export requests must:

* require authentication
* export only the requesting user's data
* use a documented format
* avoid temporary public URLs
* avoid storing exports in web-accessible temporary directories

Export security must be reviewed before implementation.

Export is not required for the first implementation milestone.

---

# 34. Data Deletion

Users may delete individual entries through the documented API.

Deleting a Health resource must always verify ownership.

A future "Delete all my Health data" feature should remove all personal Health records belonging to the authenticated user.

Health must not silently delete historical entries merely because a module is disabled.

---

# 35. Account Lifecycle

Health should not intentionally retain orphaned personal data after the corresponding Nextcloud account has been permanently removed.

Account lifecycle handling must use an official Nextcloud user lifecycle mechanism compatible with the minimum supported Nextcloud version.

Do not implement database cleanup by reaching into private Nextcloud internals.

---

# 36. No Automatic Sharing

Health data is private by default.

The MVP does not support:

* public sharing
* user-to-user sharing
* manager sharing
* doctor sharing
* link sharing
* group sharing

Do not reuse Nextcloud file-sharing concepts automatically for Health records.

Sharing Health data is a future product decision and requires a dedicated security model.

---

# 37. No Public Endpoints

The Health data API has no anonymous public endpoints.

Do not use:

```php
#[PublicPage]
```

for Health API data.

Public pages may only be introduced through a dedicated future security review.

---

# 38. No Custom Cryptography

The MVP does not implement application-specific encryption.

Do not create:

* custom encryption algorithms
* custom password-derived encryption
* home-grown key storage
* reversible obfuscation presented as encryption

If application-specific encryption is introduced later, it requires a separate architecture and threat-model review.

---

# 39. Dependencies

Security-sensitive functionality should use official Nextcloud APIs where available.

Do not introduce external packages for functionality already safely provided by Nextcloud.

New dependencies must have a clear purpose.

The MVP intentionally keeps external frontend dependencies minimal.

General UI:

```text
@nextcloud/vue
```

Charts:

```text
Chart.js
```

Do not add another UI or charting framework without an architectural decision.

---

# 40. Security-critical API Tests

API tests must explicitly verify user isolation.

At least two users are required:

```text
health-user-a
health-user-b
```

Required scenarios include:

1. User A creates an entry.
2. User A can read it.
3. User B cannot read it.
4. User B cannot update it.
5. User B cannot delete it.
6. User A can update it.
7. User A can delete it.

Equivalent ownership tests are required for:

* goals
* reminders
* user configuration where applicable

---

# 41. Authentication Tests

API tests must verify that unauthenticated access to personal Health endpoints fails.

Tests must not disable authentication merely to simplify test setup.

---

# 42. Validation Tests

API tests must cover invalid values including:

* unknown metric
* value outside allowed range
* invalid option
* invalid context
* invalid timestamp
* malformed cursor
* excessive page size
* excessive batch size
* oversized note

---

# 43. OpenAPI and Security

Security-related requirements must be represented correctly in generated API documentation.

Do not accidentally document authenticated endpoints as public.

Public API documentation must not contain real Health examples copied from development databases.

Use synthetic example values only.

---

# 44. Development Data

Development environments must use synthetic Health information.

Do not copy production Health databases into local AI-assisted development environments.

Do not provide real employee or patient Health information to coding agents.

---

# 45. AI Coding Agents

AI coding agents must treat security requirements as hard constraints.

Agents must not:

* weaken authentication
* add `#[PublicPage]`
* add `#[NoCSRFRequired]` to data APIs
* add `#[CORS]`
* expose other users' data
* remove ownership checks
* log Health values
* log API payloads
* disable security tests
* invent custom authentication
* invent custom cryptography

to make implementation easier.

If a feature appears to require weakening one of these controls, implementation must stop and the architecture must be reviewed first.

---

# 46. Security Definition of Done

A Health API feature is complete only when:

1. authentication is enforced
2. regular users can access their own functionality
3. user ownership is enforced server-side
4. cross-user access is tested
5. input is validated server-side
6. no sensitive Health data is logged
7. CSRF protections remain intact
8. unrestricted CORS is not enabled
9. API errors do not leak internal information
10. OpenAPI documentation correctly represents the endpoint
11. security-critical API tests pass

---

# 47. Security Priority

When implementation convenience conflicts with security:

> Security wins.

When feature richness conflicts with privacy:

> Privacy wins.

When an AI coding agent is uncertain whether a Nextcloud security mechanism can safely be bypassed:

> Do not bypass it.

## Daily notes

Daily notes are sensitive Health data. Reads and writes are scoped to the authenticated user and date; note contents are never logged. The API and frontend handle only plain text, never stored or rendered HTML. Native Unified Search is opt-in per user (`searchDailyNotes` defaults to `false`); when it is disabled the provider must not query notes, and when enabled every query remains scoped to the requesting user.

## Goals and notifications

Goals, revisions, derived progress, and reminder state are personal Health data. Every goal lookup and source query is scoped to the authenticated owner; no client user ID is accepted, and reminder state has no public API. The background job evaluates each stored goal using that goal's owner and configuration only. Notifications are sent through the authenticated owner's Nextcloud notifications account and may identify the associated metric or topic using its localized label and icon. They must not include measurement values, goal target values, current progress or completion, remaining amounts, percentages, notes, journal text, dates, or detailed Health history, and they link only to the authenticated Goals route. No Health request, response, progress result, or reminder evaluation is logged.

## Integrated PWA local storage and synchronization

Only the PWA shell, manifest, service worker, and referenced static assets are public. Health OCS endpoints remain authenticated, owner-scoped, CSRF-protected, and without unrestricted CORS. Login Flow v2 creates a revocable app password; the normal account password is never stored. The app password and non-value metric configuration are held in the PWA's origin-scoped IndexedDB account record. Cache Storage contains no authenticated Health API response.

Pending writes are stored in an origin-scoped IndexedDB outbox with only the metric, required value/unit, timestamp or local date, state, and random operation ID. Values are never placed in URLs, logs, analytics, manifests, notifications, or Cache Storage. Confirmed operations are removed. Authentication and transient failures retain them for explicit retry. Disconnect warns that pending writes will be removed, attempts app-password revocation, and clears both account and outbox data. Entry and measurement operation IDs are owner-scoped and idempotent; daily-value PUTs are naturally idempotent.

The PWA technical diagnostics dialog is generated only from a fixed allow-list: app/build version, server origin, online state, authentication state without credentials, synchronization state, last successful synchronization time, generic outbox count, and service-worker state. It never reads or serializes Health values, metric configuration values, goals, Journal content, app passwords, tokens, authorization headers, cookies, or encryption material. Connection notices likewise contain only generic technical state and count, never pending entry details.
