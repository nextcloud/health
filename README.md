# Nextcloud Health

**A private health journal for Nextcloud.**

Health is a community-maintained Nextcloud app for privately recording everyday health and wellbeing data in your own Nextcloud.

Version 3 is a substantial rewrite of the original Nextcloud Health app. The new direction focuses on a calm personal journal: record values, review them over time, set optional goals, and use reminders when helpful — without diagnoses, automated medical interpretation, causal claims, or a universal “health score”.

> Health is not a medical device and does not provide medical advice, diagnosis, or treatment recommendations.

## Health v3: a new direction

The original Health app provided modules for areas such as weight, feeling, measurements, sleep, smoking, activities, and medication.

Health v3 keeps the idea that personal health data belongs in a private, self-hosted environment, but rebuilds the app around a simpler journal-oriented model with a new backend and frontend architecture.

The rewrite is designed to:

- keep personal health data scoped to the signed-in user
- provide a consistent journal for daily values and timestamped entries
- support optional check-in and check-out routines
- provide goals and deterministic reminders without coaching or diagnosis
- provide statistics without interpreting whether a value is “good” or “bad”
- expose a versioned API so the web UI and future clients can use the same domain model
- integrate with native Nextcloud features such as Dashboard, Search, Notifications, themes, accessibility, and localization
- preserve existing Health data during upgrades rather than destructively dropping legacy data

## Features

### Journal

The Journal is the primary place to record and review health data for a selected day.

It supports:

- a Daily Note
- Daily Values such as weight, body fat, waist, hip, muscle percentage, steps, and Job Satisfaction
- timestamped measurements such as temperature, oxygen saturation, blood glucose, pulse, and blood pressure
- journal metrics such as stress, energy, mood, hydration, and breaks
- compact create/edit modals
- check-in and check-out routines
- optional daily goal indicators
- user-configured display units

### Goals

Goals are optional and remain separate from the underlying health data.

Health supports daily, weekly, monthly, and long-term goals where meaningful. Progress is derived from recorded data rather than stored as a separate health value.

Optional “Gentle reminders” use deterministic rules. They do not use AI to judge health, infer causes, or tell users what they should feel.

### Statistics

Statistics provides a configurable time-series view for numeric metrics.

Depending on the metric, Health can display:

- line series for numeric values and measurements
- stacked bars for hydration categories
- stacked bars for break categories
- goal thresholds as dashed overlays
- average, minimum, maximum, and source-record counts for the selected period

Statistics is descriptive only. It does not automatically label changes as improvements or deteriorations.

### Nextcloud Dashboard

Health integrates with the native Nextcloud Dashboard and provides a compact view of current Health data and common quick actions.

### Nextcloud Search

Daily Notes can optionally be included in Nextcloud Search.

Search integration is disabled by default and searches only the signed-in user's Daily Notes.

### API

Health v3 is API-first. The web interface uses the same versioned application API intended for other authorized clients.

API documentation is maintained with the source and generated from the application's API definitions where applicable.

## Privacy and security

Health data is sensitive.

The application is designed around these rules:

- authenticated users can access only their own Health records through the application API
- clients never select an arbitrary owner/user ID for Health data
- disabled metrics remain stored and can be re-enabled without losing history
- no unrestricted CORS or public Health-data endpoints are provided
- Health does not add application-level access for employers, managers, or other users

### Reporting security issues

**Do not create public GitHub issues for security vulnerabilities or suspected security vulnerabilities.**

Please report security issues privately by email:

**mail@datenangebot.de**

Include enough information to reproduce and understand the issue, but avoid sharing sensitive data from a real Health installation.

Public disclosure before a fix is available can put users at risk.

## Installation

### App Store

Once a compatible Health v3 release is published, the recommended installation method is the Nextcloud App Store.

Open **Apps** in Nextcloud, find **Health**, and enable it.

### Testing a Git branch

Health intentionally keeps its compiled frontend assets in the repository. This makes it possible to clone a test branch into a Nextcloud app directory without requiring a frontend build first.

For example:

```bash
cd /path/to/nextcloud/apps
git clone --branch feat/health-v3 --single-branch \
  https://github.com/nextcloud/health.git health
```

Then enable the app through Nextcloud's app management or with `occ`.

The exact supported Nextcloud versions are declared in `appinfo/info.xml`.

> A Git branch is development software. Use a release for production installations once one is available.

## Upgrading from an older Health version

Health v3 is a rewrite.

The migration path is designed to preserve legacy Health tables and data while introducing the v3 data model through newer migrations.

Before upgrading a production installation:

1. create a database and Nextcloud backup
2. read the release notes and changelog
3. verify that your Nextcloud version is supported
4. test the upgrade on a non-production copy when possible

Do not downgrade an upgraded production database unless the release notes explicitly document that path.

## Bugs and feature requests

Non-security issues are welcome on GitHub.

Before opening an issue:

- update to the latest applicable Health version or development branch
- check whether the issue has already been reported
- include a clear description of what happened
- explain what you expected to happen
- include steps to reproduce the problem
- include relevant Nextcloud and Health versions
- include browser/device information for UI issues where useful
- include logs only after removing private health data, credentials, tokens, and other sensitive information

Feature requests are also welcome, but this is a community project and there is no guarantee that a requested feature will be implemented.

## Vibe coding and contributions

Health v3 is intentionally developed primarily through **AI-assisted / vibe-coding workflows**.

That is a deliberate part of the project's development model, not an attempt to hide how the software is produced.

Human maintainers remain responsible for deciding the product direction, handling security reports, and deciding what is merged.

### Pull requests

Focused fixes, tests, documentation improvements, accessibility improvements, and well-scoped contributions are welcome.

Large code rewrites or broad architectural replacement pull requests are less likely to be accepted. Because the application is maintained through an AI-assisted workflow, larger implementation changes are usually reproduced or adapted through that workflow rather than merged as independent rewrites.

If you want to propose a substantial change, opening an issue first is strongly recommended.

Please keep contributions:

- focused
- reproducible
- documented
- compatible with the existing domain model and privacy rules
- free of unrelated refactors

Contributions must follow the repository's licensing and DCO requirements.

## Development

Health is a classic Nextcloud PHP application with a Vue 3 frontend.

Typical development requirements include:

- a supported Nextcloud development environment
- PHP and Composer
- Node.js and npm
- the versions declared by the repository's package and Composer metadata

Install frontend dependencies:

```bash
npm ci
```

Build the frontend:

```bash
npm run build
```

Use the available project scripts for type checking, linting, style checking, and tests.

Compiled frontend assets are intentionally committed. When changing frontend source code, regenerate and commit the corresponding production bundles in the same change.

Do not commit `node_modules/` or other local dependency/cache directories.

## Localization

English is the source language for user-facing strings.

Visible UI strings should use Nextcloud's localization functions. Translations are maintained through the normal Nextcloud localization workflow.

## Community funding

Health is a community app. Donations can help cover the time and infrastructure required for maintenance, testing, documentation, and releases.

Donations do not purchase feature priority, private health-data access, or guaranteed support.

## License

Health is free software licensed under the **GNU Affero General Public License, version 3 or later (AGPL-3.0-or-later)**.

See `COPYING` and the repository license information for details.

## Acknowledgements

Health v3 builds on the history of the original Nextcloud Health app and the work of its previous maintainers and contributors.

The rewrite deliberately keeps the original project's core idea — **tracking personal health data privately in Nextcloud** — while moving the application toward a new journal-focused architecture and user experience.

Thank you to everyone who created, maintained, translated, tested, documented, and used Health over the years.
