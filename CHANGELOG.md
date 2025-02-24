# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0]

### Added 

- Support for Nextcloud 31
- translations via Transifex
- preview widgets for links to XWiki pages
- a smart picker for XWiki Links

### Fixed 

- PDF download working again
- deprecated PHP attributes replaced

### Removed

- Support for Nextcloud 25 & 26


## [0.1.2] - 2024-04-11

### Added

 - Support for Nextcloud 28, 29 and 30

## [0.1.1] - 2023-06-28

### Fixed

 - Fixed a case where a connection error to XWiki could cause PHP errors.
   A typo also caused PHP errors in other cases.

## [0.1.0] - 2023-06-28

### Changed

 - The way Nextcloud authenticate to XWiki has been revamped. It does not
   require the Nextcloud extension for XWiki anymore and uses the standard
   mechanism provided by the OICD Provider for XWiki. The Nextcloud extension
   is still advertised because it will be needed for future features.
 - Removed icons in buttons in the administration section. These icons are not
   provided by Nextcloud anymore, and adding a dependency on some package to
   restore them is not worth it.

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
