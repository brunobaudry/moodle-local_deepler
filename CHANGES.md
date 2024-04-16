# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
