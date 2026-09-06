# Health v2 – Minimum Viable Product

## Goal

The first version of Health v2 provides a private, configurable health journal inside Nextcloud.

The MVP must already be useful in daily life while remaining small enough to be implemented, tested, and maintained with AI-assisted development.

The MVP focuses on:

1. configuring what the user wants to track
2. recording health and wellbeing data with minimal effort
3. optional check-in and check-out routines
4. personal daily goals
5. daily, weekly, and monthly overviews
6. simple descriptive statistics

The MVP does not attempt to interpret health data.

---

## Core User Journey

A new user should be able to:

1. Open Health.
2. Select the metrics they want to track.
3. Configure optional check-in and check-out metrics.
4. Enter values during the day with very few interactions.
5. See today's journal entries.
6. See progress toward personal daily goals.
7. Review the current week.
8. Review a month.
9. Open statistics for an individual metric.
10. Change their configuration at any time.

Until persistent module configuration is implemented, Stress is initially enabled for a new user. Users will be able to change this default once module configuration is available.

---

## MVP Navigation

The main navigation contains:

- Today
- Journal
- Overview
- Statistics
- Settings

### Today

The primary working view.

It contains:

- optional morning check-in
- quick-entry actions
- today's goals
- recent journal entries
- optional evening check-out

### Journal

Chronological view of recorded entries.

Users can:

- browse entries by day
- add an entry
- edit their own entry
- delete their own entry
- optionally add a short note

### Overview

Provides aggregated visual summaries.

Initial periods:

- week
- month

The overview should emphasize:

- goal completion
- tracked days
- simple averages
- simple visual indicators

### Statistics

Detailed history of one selected metric.

The user can choose:

- metric
- time period

Initial periods:

- 7 days
- 30 days
- 3 months
- 1 year

Statistics are descriptive only.

Users may save a private reusable Statistics configuration containing its selected metrics and time period. Saved views do not store or interpret Health values.

### Settings

Users configure:

- enabled metrics
- check-in metrics
- check-out metrics
- personal goals
- reminders where supported
- metric-specific preferences

---

# Metrics

The MVP supports the following built-in metrics.

## Stress

Type:

- scale

Range:

- 1 to 5

Suggested labels:

1. very low
2. low
3. moderate
4. high
5. very high

---

## Energy

Type:

- scale

Range:

- 1 to 5

---

## Mood

Type:

- scale

Range:

- 1 to 5

---

## Hydration

Type:

- event / counter

Quick-entry options:

- small glass
- large glass
- coffee
- other drink

The MVP does not require milliliter-perfect tracking.

Users may define a personal daily hydration goal based on number of drinks.

---

## Breaks

Type:

- event / counter

Quick-entry options:

- short break
- regular break

Optional durations may be added later.

---

## Kilocalories

Type:

- daily numeric value

Unit:

- fixed canonical `kcal`

Kilocalories accepts non-negative numeric values, including decimals. It has no unit selector because its stable metric meaning is specifically kilocalories per local day.

---

## Fruit

Type:

- accumulated daily counter

Unit:

- fixed canonical `pieces`

Fruit accepts non-negative whole numbers. Journal supports direct count entry and the quick-entry PWA provides a preferred `+1` interaction.

---

## Movement

Type:

- event or numeric value

Initial options:

- short walk
- active break
- manual step count

No automatic device integration is part of the MVP.

---

## Sleep

Type:

- duration
- optional recovery rating

Users can record:

- sleep duration
- how rested they feel from 1 to 5

---

## Weight

Type:

- numeric value

Unit:

- kg

The underlying architecture should allow additional units later.

---

# Check-in

The check-in is optional.

Users choose which enabled metrics appear.

Typical configuration:

- energy
- mood
- stress

A check-in should take only a few seconds.

The resulting values are stored as normal journal entries with a check-in context.

---

# Check-out

The check-out works like the check-in.

Typical configuration:

- energy
- mood
- stress

This allows the user to compare the beginning and end of the workday without Health attempting to explain the difference.

---

# Quick Entry

Frequently used actions should require as few interactions as possible.

Examples:

- Add small glass
- Add large glass
- Add break
- Add short walk
- Record stress
- Record energy

After saving, the user should remain on the current page.

---

# Goals

The MVP supports personal daily goals.

Examples:

- 6 drinks per day
- 3 breaks per day
- complete check-in
- complete check-out

Goals are defined by the user.

Health visualizes goal completion but does not judge the user.

---

# Daily Status

Each configured metric may optionally expose a simple daily status.

Example:

- goal reached
- partially reached
- no data

Visual indicators may use colors and icons.

Colors must never be the only means of communicating status.

---

# Weekly Overview

The weekly overview displays seven days.

For configured metrics it may show:

- average value
- total count
- goal completion
- number of tracked days
- daily status indicators

Example:

Hydration:
4 of 5 workdays reached goal

Breaks:
3 of 5 workdays reached goal

Stress:
Average 2.8 / 5

Energy:
Morning average 3.8 / 5
Evening average 3.2 / 5

No interpretation is generated.

---

# Monthly Overview

The monthly view provides a compact visual overview.

Possible representations:

- calendar heatmap
- status dots
- goal completion percentage
- average values

The purpose is to help users visually recognize their own periods and developments.

Health does not explain why those developments occurred.

---

# Statistics

Statistics provide metric history.

For scale and numeric metrics:

- line chart
- average
- minimum
- maximum
- number of entries

For counter/event metrics:

- totals
- daily averages
- goal completion

The user may eventually compare two metrics, but this is not required for the first implementation milestone.

---

# Notes

Journal entries may include an optional short text note.

Notes are intended for personal context such as:

- stressful project day
- vacation
- sick
- worked from home
- travel

Health does not analyze these notes in the MVP.

---

# Privacy Requirements

All Health data belongs to the logged-in user.

Users may only access their own:

- configuration
- journal entries
- goals
- statistics

There is no organization-wide or administrator-facing health dashboard.

---

# API Requirements

The MVP must be API-first.

The Vue frontend must use the same application API that future external clients can use.

The API must support at least:

- metric configuration
- creating entries
- reading entries
- updating entries
- deleting entries
- goals
- daily summaries
- weekly and monthly overviews derived from daily summaries
- statistics

The weekly and monthly UI overviews use:

```text
GET /api/v2/summaries/days?from=&to=
```

They do not require separate week or month endpoints.

The API must be versioned from the beginning.

Initial version:

`v2`

---

# Explicitly Out of Scope

Do not implement these features as part of the MVP:

- AI analysis
- automatic pattern detection
- medical recommendations
- diagnoses
- employer dashboards
- team statistics
- calendar integration
- Apple Health
- Google Health Connect
- wearable integrations
- automatic step synchronization
- nutrition tracking
- medication tracking
- social features
- competitions
- rewards
- gamification
- BEM workflows
- occupational health management
- external healthcare providers

These may be considered after the core product is stable.

---

# MVP Success Criteria

The MVP is successful when a user can:

- configure a personal journal
- record daily health information in seconds
- use check-in and check-out
- track simple goals
- review their week
- review their month
- view the history of a metric
- understand the interface without training
- use Health without sharing their data with anyone else

The MVP should feel useful even if the user never connects an external service or device.

## Goals slice

The MVP goal slice supports one logical owner-scoped target for each target/period identity, so one metric may have multiple meaningfully different periods without allowing an identical duplicate. It supports historical revisions, derived day/week/month/long-term progress, pause and retirement, and opt-in Gentle reminders. Long-term latest-value goals expose baseline-aware directional progress. Job Satisfaction is a daily integer scale from 1 to 5, disabled by default, available in the Journal and optional check-in/check-out routines.

## Quick-entry PWA slice

The Health app includes an installable PWA at `/apps/health/pwa/`. After initial loading and Nextcloud Login Flow v2 authorization, it can queue minimum write payloads in IndexedDB while offline and synchronize them through the existing owner-scoped Health API. Its equal-sized metric launcher grid opens entry dialogs that use direct buttons for finite choices, numeric Save plus direct increment for counters, and numeric inputs for arbitrary values. Its diagnostics are technical troubleshooting only and exclude Health and credential data. It does not display recorded Health values or derived data.
