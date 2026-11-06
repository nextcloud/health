# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [3.3.1]

### Fixed

- Keep Statistics chart axes and tooltips readable in dark themes and refresh chart colors when the system theme changes ([#337](https://github.com/nextcloud/health/issues/337)).
- Avoid converting Statistics display units twice in tooltips and accessible value announcements ([#336](https://github.com/nextcloud/health/issues/336)).
- Parse grouped and decimal numbers in PWA entry fields according to the account locale ([#321](https://github.com/nextcloud/health/pull/321)).

### Changed

- Refresh bundled dependencies and development tooling.
- Declare compatibility with Nextcloud 36.

## [3.3.0]

### Added

- Add fixed-unit daily Kilocalories and whole-number Fruit counter metrics across Journal, Goals, Statistics, saved views, reminders, and configuration.
- Add an integrated offline-first Health quick-entry PWA with Login Flow v2 authentication and idempotent synchronization.
- Add Allergies as an optional timestamped symptom measurement with owner-scoped Journal, Statistics, saved-view, configuration, and PWA support.

### Fixed

- Show baseline-aware directional long-term Weight goal progress in today's Journal.
- Allow independent goal periods for the same metric while rejecting identical target/period duplicates.
- Store Kilocalories as timestamped measurements so multiple values recorded on one day aggregate correctly.

## [3.1.2]

### Fixed

- Fix goal reminder notifications that showed the literal `{topic}` placeholder.

## [3.1.1]

### Fixed

- Draw configured goal targets as visible dashed lines in Statistics charts.
- Restore direct reloads of saved Statistics view links.

### Changed

- Improve Statistics chart scale padding and connect measurements across missing dates.
- Align Statistics and saved-view spacing with the Journal and Goals views.

## [3.1.0]

### Fixed

- Small fixes

### Added

- Persistence for statistic views

## [3.0.4]

### Fixed

- Fix Statistics chart rendering for regional locales.
- Improve compatibility with released database migrations.

### Added

- First release
