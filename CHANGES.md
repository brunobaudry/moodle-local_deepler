# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.8.2]
### Added
- **Lesson** mod type was missing.
- **URL** external url cannot be multilang. Removed from translatable fields but left it to help translators getting the context.
- **Wiki** page title are now displayed but not translatable as it would break the links.
- Added API **multiple token mapping**. An additional admin page is there for you to create a rule to map a user's Moodle user attribute (including custom profiles) to one of your 
  DeepL api key. 
- Added non core questions:
  - qtype_calculatedmulti
  - qtype_calculatedsimple
  - qtype_ddwtos
  - qtype_formulas
  - qtype_fileresponse
  - qtype_kprime
  - qtype_mtf
  - qtype_multichoiceset
  - qtype_pmatch
  - ... more to come
- Prepared a new YAML definition so you could grab you custom mods and qtypes db field to be scanned (next release)

### Fixed
- Session modules were scanned twice.
- Less DB calls for field discovery.

## [1.7.3]
### Added
- Non core Kprime qtype.

### Fixed
- silly cmid jam in book and wiki parsing.
- tiny preload, only only loads after text is retrieved from Deepl, as it would not inject translation into the loaded tiny.

## [1.7.2]
### Added
- Breadcrumb's subs limit. (issue #73)


## [1.7.1]
### Fixed
- Sections' id generated from header text for breadcrumb could break the html.
- TinyMCE slowing down the pages on initial load. Now it will be loaded only if the user wants to modify the translation. If TinyMCE is your prefered editor the plugin will initiate the page with a simple editable div. Only should you need to modify the recieved translation from
  DeepL, the plugin will load a basic TinyMCE for you to do so upon clicking inside the field.

### Added.
- TinyMCE load on demand.

## [1.7] 2025-05-21
### Fixed
- Book chapter title wrong db field.
### Added 
- source text iFrame toggler.

## [1.6.9.2] 2025-05-08
### Fixed
- Exception upon saving settings in cookies with a free account.

## [1.6.9] 2025-05-05
### Fixed
- Fixed qmatch throwing exceptions.
- Added attibutes to **try** reducing tinyMCE frantic http calls.

## [1.6.8] 2025-04-30
### Fixed
- migration db order...

## [1.6.7] 2025-04-30

### Added 
- Different color code for rephrase statuses.
- Updated readme with new features.

### Fixed
- Increase t_lang field size from 2 to 16 char to include sub languages formats.

## [1.6.5] 2025-04-29

### Fixed
- Bad string conception for the 'Show "Hidden from students" ' string.
- Translation status not correctly updated.

## [1.6.4] 2025-04-29

### Fixed
- Exception with plugin without 'purpose' field (issue #63).


## [1.6.2] 2025-04-29

### Added
- Added doc for save to specific lang when Moodle instance have sub-langs installed.

### Fixed
- Exception with plugin without 'purpose' field (issue #67).

## [1.6.1] 2025-04-28

### Added
- Added support for @DeepL rephrasing (text improvement) API.
- Added all advanced setting stored in Cookies.
- mlang tags listed when hovering the toggle lang button.

### Fixed
- Exception with multilang filter in moodle < 4.5

## [1.5.1] 2025-04-01

### Changed
- Massive PHP refactoring for reduced db calls, better flexibility for future features. PHP mess detector close to 0.
- Moving maturity to BETA.
- Removed the 'allow sub langs to be mapped...' setting as did not make sense anymore with DeepL's new source/target.
- Improved UI
  - Better Section > Module > Field nesting and display.
  - Edit in place Button is now at item (section, module, question) level rather than field level.
  - Improved icon and field names translations (Moodle's _strings_) capture. 

- ## Added
- Section > Module > Sub breadcrumbs.
- Improved error capture.

### Fixed
- Language Strings UI should all display except for the ones with increment (like question's 'hints')

### Todo
- Check the "enhancement" list https://github.com/brunobaudry/moodle-local_deepler/issues
- Fetch PLUGINFILES links to display questions' images.
- Improve user doc.
- Add dev doc.


## [1.3.4] 2025-03-05

### Added
- Glossary ids are now saved in cookies (for 1 month) by course and source-target language pairs. As reported with issue #56.
- More activity filed captured.

### Changed
- No more Deepl api calls are made directly from js, only through Moodle's external api now. The plugin in now only connect to Deepl's api using http POST method. More secured as 
  the Deepl token was exposed in with GET calls. Plus it will be deprecated ads of friday 
  14th 2025 by Deepl.
- JS code refactored in modules for more readability and flexibility.
- UI changes:
  - Now you cannot select the main source language. Change Moodle's to set the main source lang.
  - Improved error reporting.

### Fixed
- Issues when checking the activity contexts (fed by the tab id instead of the cmid) issue #54.
- Improved capture of Tiny instances.

### Todo
- Check the "enhancement" list https://github.com/brunobaudry/moodle-local_deepler/issues
- Language Strings UI for all filed names 

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
