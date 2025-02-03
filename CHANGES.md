# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
## [1.2.7] 2025-02-03

### Fixed
- Duplicate const definition in webservice (issue #53)

## [1.2.6] 2025-01-31

### Fixed
- Side effect with last error correction...

## [1.2.5] 2025-01-03

### Fixed
- Bad error catching when listing the questions slots.

## [1.2.4] 2024-12-20

### Fixed
- External update_translation.php was not working on errors.

## [1.2.3] 2024-12-18

### Fixed
- JS require caching issues ?

## [1.2.2] 2024-12-16

### Added

- Removed the 'Use pro or free admin setting', as the php DeepL lib does it.
- If the user has capability show the link in course even if incorrectly set in admin, but with message to instruct admin to do so.

### Fixed

- Display distinct error page for translator when key API not set.
- Fixed Observers not caught.
- Fixed section with no activties arry was not set triggering an exception.
- Fixed access to private cminfo id.

## [1.2.0] 2024-12-05

### Added

- Added TOC to the README.md

### Fixed

- The JS displayed a dummy error upon saving to DB, though everything was saved correctly.

## [1.1.4] 2024-11-30

### Fixed

- Error message display when field is too long for DB
- Capability would not allow course level override

## [1.1.3] 2024-11-29

### Fixed

- Typos in strings

## [1.1.0] 2024-11-28

### Added

- Core quizzes questions.
- Improved layout.
- Minimal db field scan is now as admin setting.
- Pre escape setting.
- Improved DB calls by
    - removing field retrieving before saving as this would also overwrite the changes made in editors (if any)
    - Grouping ajax calls when batch saving.

### Fixed

- Edit in place links for book pages and other subs.
- Advanced settings are now updated upon changes therefore correctly passed to deepl.
- Prevent to click the status icon or to batch save if the status is something else than "Save".

### Todo

- Get all the subfields string correct.
- See the backlog in the [issues](https://github.com/brunobaudry/moodle-local_deepler/issues)

## [1.0.2.5] 2024-11-13

### Added

- Wiki subpages
- run_test (phpunit test includes now the init)

### Fixed

- Some subfield name captures
- Sub pages edit links for Books and Wiki

## [1.0.2.4] 2024-11-12

### Fixed

- Test
- Moodle CI futur proofed by Luca.

## [1.0.2.3] 2024-11-12

### Fixed

- Fixed php test with dynamic affectation in lang_helper.
- Fixed and improved code (for php test with the field name catcher).

## [1.0.2.2] 2024-11-12

### Added

- French strings
- Field strings
- Improved error message when db field reach its max

### Fixed

- Security constraints were preventing from course and section access.

## [1.0.2] 2024-11-01

### Added

- LaTeX syntax escaping including :
    - Admin default.
    - Enabler at page level.

### Fixed

- Complex text (with code and mixed quotes) were breaking the HTML attributes.

### Todo see issue list

## [1.0.1] 2024-07-09

### Added

- Admin settings to allow sub languages codes to be treated as their main

### Fixed

- minor bugs

### Todo

- Mustache the page header.
- More tests.
- Add glossary interface.
- Add user mapping to API key.
- Refactor API with ©Deepl PHP libs.
- Insert usage control (per user).
- Purge vendor's library of duplicates from Moodle's core vendor.
- Recurvise subcontent parsing (wikis ...)

## [1.0.0] 2024-05-07

### Added

- UI display of activity icons and blocks

### Improvements

- Add modal waiting UI when saving huge courses with multiple activities.
- Don't show the menu entry at all if no API key is present.

### Fixed

- Topics (Section) names are overidden when they hold a non blank summary.
- Sub pages activities/resoures was not listed.

### Todo

- Mustache the page header.
- More tests.
- Add glossary interface.
- Add user mapping to API key.
- Refactor API with ©Deepl PHP libs.
- Insert usage control (per user).
- Purge vendor's library of duplicates from Moodle's core vendor.
- Recurvise subcontent parsing (wikis ...)

## [0.9.9] 2024-04-16

### Added

- Row selection and main buttons made sticky.
    - Added floating "Save all" button.
- Word and char calc is now in JS to be reset upon filtering.
- Character count is compared to your ©Deepl's account limt allowed for the API key.
- Source and target languages, available in your Moodle instance, are compared to ©Deepl's API available languages.
- Tour guide (to be installed in your Moodle instance for user training).
- Different source selector. For content already in mixed languages.

### Removed

- Word and char count in php.

### Improvements

- Simplified bootstraps.

### Todo

- Mustache the page header.
- More tests.
- Add glossary interface.
- Add user mapping to API key.
- Refactor API with ©Deepl PHP libs.
- Insert usage control (per user).
- Purage vendor's library of duplicates from Moodle's core vendor.

## [0.9.5] - 2024-01-22

### Fork

https://github.com/jamfire/moodle-local_deepler

### Added

- Source lang can be any of the avaiblable lang.
- deepl api advanced setting.
    - Formality, Glossary id, tags handling (HTML/XML, Non splitting/Splitting/Tags to ignore), context
- Image display in preview
    - also highlights alt text when non loaded image tags in editors (@@PLUGINFILE@@).
- User preferred editors can now be plaintext, Atto, Tiny and Marklar.
- Course activities are now ordered as per course layout and grouped by modules/sections.

### Removed

Auto translation is removed. Hence, when calling the ©Deepl API, transaltion must be reviewed before storing it into DB.

### Improvements

- Several UI improvements
    - Update status
    - Api call stages
- Test coverage
- Rewrote JS code to ES2005

### Todo

- Abstract translation API calls to use other providers
- rewrite module templating with mustache for better flexibility
