# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.0.6] - 2023-05-15

### Fixed

 - Fixed a bug that prevented the navigation tree from appearing when only one
   XWiki instance was registered ([#10](https://github.com/nextcloud/xwiki/issues/10))

## [0.0.5] - 2023-04-03

### Fixed

 - Fixed a bug that prevented users from disabling instances
 - Non admin users can now save their preferences and get access to XWiki ([#6](https://github.com/nextcloud/xwiki/issues/6))

### Added

 - Support for Nextcloud 26 and 27

### Changed

 - Updated dependencies

## [0.0.4] - 2023-01-26

### Fixed

 - Now works on the official Nextcloud Docker image. AJAX calls are done the
   right way, see [this pull request](https://github.com/nextcloud/nextcloud-router/pull/446)
   if you are curious about the technical reasons.

## [0.0.3] - 2023-01-20

### Fixed

 - Don't use `$_SERVER['HTTPS']`, which is not always set correctly in all installs.
   Use Nextcloud provided methods to generate URLs instead. In some case, the
   XWiki application was generating an HTTP redirect URI on https instances, which
   was wrong and is now fixed.

## [0.0.2] - 2023-01-18

### Added

 - Users can disable wikis they don’t want to use from their personal settings
 - When registering a wiki, its URL is autofixed when possible (a  missing
   '/xwiki' is added for instance)
 - A CHANGELOG.md file

### Changed

 - The welcome screen is hopefully nicer to read
 - Registering a wiki is now more user-friendly, with guiding onboarding
   messages
 - When registering a wiki, `instance_uri` is passed to XWiki, so XWiki can
   produce correct links to the Nextcloud instance

## [0.0.1] - 2023-01-04

Initial release
