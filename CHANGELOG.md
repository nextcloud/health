# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Add fixed-unit daily Kilocalories and whole-number Fruit counter metrics across Journal, Goals, Statistics, saved views, reminders, and configuration.
- Add an integrated offline-first Health quick-entry PWA with Login Flow v2 authentication and idempotent synchronization.

### Fixed

- Show baseline-aware directional long-term Weight goal progress in today's Journal.
- Allow independent goal periods for the same metric while rejecting identical target/period duplicates.

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
