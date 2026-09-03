# Health v2 – Product Definition

## Vision

Health is a private health and wellbeing journal for Nextcloud.

It helps users consciously track selected aspects of their personal health and wellbeing in everyday life and at work with minimal effort.

Health is not a medical diagnostic tool, not a personal coach, and not an employee monitoring system.

The user owns and controls their health data.

## Core Principles

- Privacy first
- User-controlled
- Low-friction data entry
- API first
- No medical diagnosis
- No automated interpretation of health conditions
- No employer access to individual health data
- Modular and configurable
- Useful without external devices or third-party services

## Core User Experience

Users configure which aspects of their health they want to track.

Possible metrics include:

- Stress
- Energy
- Mood
- Hydration
- Breaks
- Movement
- Sleep
- Weight

Only enabled metrics appear in the user's journal.

Each metric can be configured to:

- appear during check-in
- appear during check-out
- be entered manually
- trigger optional reminders

## Journal

The journal is the central concept of Health.

A journal entry can represent:

- a health metric
- a habit
- an activity
- a check-in
- a check-out
- an optional personal note

Entries should normally require only one or two interactions.

## Today

The Today view provides:

- morning check-in
- quick metric entry
- current daily goals
- reminders
- recent journal entries
- evening check-out

## Goals

Users can define personal goals such as:

- drink 6 glasses
- take 3 breaks
- keep stress within a personal target range
- complete a daily check-in

Goals can be evaluated per day and summarized across weeks and months.

## Statistics

Health provides descriptive statistics only.

Users can view:

- daily values
- weekly summaries
- monthly summaries
- yearly trends
- goal completion
- metric history
- reusable private saved views of selected metrics and periods

The application should visualize data but should not automatically claim causal relationships.

The user interprets their own patterns.

## Privacy

Health enforces application/API-level user isolation. Through the Health application and Health API, personal health information is accessible only to the individual user who owns it.

Health must not provide employers, administrators, managers, or other users with access to individual health information.

Administrative access to the Nextcloud instance must not imply access to Health data through the Health application.

This application-level isolation is not a claim that Health data is cryptographically hidden from the Nextcloud server operator or database administrator.

## API-first

The web frontend is a client of the Health API.

All relevant user-facing data operations must also be available through a documented API so that external clients can be supported in the future.

## Out of Scope for the Initial Product

Initially Health does not include:

- employer dashboards
- employee monitoring
- medical diagnoses
- AI health interpretation
- Apple Health integration
- Google Health Connect integration
- wearables
- social features
- challenges
- complex nutrition tracking

## Goals, progress, and Job Satisfaction

Health goals are private, user-configured targets for a small, fixed registry of journal events, daily values, and measurements. They show descriptive progress only; they do not interpret a user's health or work situation. A Job Satisfaction value is an optional daily 1–5 self-report that is disabled by default and belongs only to its owner. Gentle goal reminders are opt-in, private Nextcloud notifications that may identify their metric or topic with its localized label and icon, but never include Health values, targets, progress, remaining amounts, notes, or history.
