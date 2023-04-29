# Changelog
All notable changes to this project will be documented in this file.

## [1.6.2] - 2023-04-28
### Updated
- translations
- compatible nextcloud versions up to 27

##  [1.6.1] - 2022-11-08
### Updated
- Translations
- Things to make this app compatible with Nextcloud 25

##  [1.6.0] - 2022-07-10
### Added
- Medication module (many thanks to kenda)

### Updated
- translations

##  [1.5.1] - 2022-04-28
### Updated
- translations
- Nextcloud 24 compatible
- Add donations link

##  [1.5.0] - 2022-02-01
### Added
- API v1 - you can request and interact with every data within this app
	- The API follows the internal structure, no official formats and structures are implemented
	- have a look at the *routes.php* and the following function headers to see what data are expected
- Button to show charts with only relative data on the y-axis

### Changed
- update dependencies
- updated translations

##  [1.4.0] - 2021-10-10
### Added
- support for nextcloud 23

### Changed
- update dependencies
- merged 2 pulls (thanks)
- updated translations

##  [1.3.0] - 2021-10-10
### Added
- calculate bmi with lb unit correctly (thanks for help)

### Changed
- Use common structure for changelog (this)
- NPM Updates
- Translation updates
- CSV export use correct labels and text values
- Smaller fixes

## [1.2.2]
### Changed
- npm updates
- nc22 compatible - db fix

## [1.2.1]
### Changed
- integrate new translations
- background updates
- new urls for app-store resources

## [1.2.0]
### Changed
- many small fixes
- many string fixes (many thanks to @Valdnet)
- add translations
- removed official support for nc19, but should work
- npm updates

## [1.1.0]
### Added
- add module activities
- chart scales more detailed

### Changed
- nc21 ready
- many small fixes
- clean up database tables < old ces tables will be removed

## [1.0.2]
### Changed
- fix db definitions to work with nc21
- fix constructor parameter for mapper to work with nc21

## [1.0.1]
### Changed
- remove beta from title

## [1.0.0]
### Changed
- various bugfixes

## [0.4.0beta]
### Added
- add module smoking
- add bmi column for the weight module
- add time range for data tables
- add download csv for data tables
- add multiple scales for charts

### Changed
- small fixes

## [0.3.0beta]
## Added
- charts for all modules
- show person creation time
- weight data bodyfat accepts decimals

### Changed
- Rewriting backend for all modules except the weight module
**Note: No data migration except weight module. This is still beta software.**

## [0.2.2beta]
### Changed
- css fixes, specially for mobile use
- css fixes for dark theme compatibility

## [0.2.1beta]
### Changed
- no limitation to db with json support

## [0.2.0beta]
### Added
- Start with changelog
- added some modules with basic data tables
- dependency for mysql, pgsql not tested
