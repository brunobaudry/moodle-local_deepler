# Deepler, Multilang Machine Translator for Moodle

[![Moodle Plugin CI](https://github.com/brunobaudry/moodle-local_deepler/actions/workflows/moodle-ci.yml/badge.svg)](https://github.com/brunobaudry/moodle-local_deepler/actions/workflows/moodle-ci.yml) [![Dependency Review](https://github.com/brunobaudry/moodle-local_deepler/actions/workflows/dependency-review.yml/badge.svg)](https://github.com/brunobaudry/moodle-local_deepler/actions/workflows/dependency-review.yml)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=brunobaudry_moodle-local_deepler&metric=sqale_rating)](https://sonarcloud.io/summary/new_code?id=brunobaudry_moodle-local_deepler) [![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=brunobaudry_moodle-local_deepler&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=brunobaudry_moodle-local_deepler)

Deepler is a local moodle plugin that provides automatic machine translation using the ©Deepl Pro Translation api.
It is developed for those who want to translate a course all on one page,
without having to navigate to each module and update manually translations and the {mlang} tags.
Translation workflow being the following:

0. Fine tune your ©Deepl's settings.
1. Select the source(s) language(s).
2. Select the target language.
3. Select the fields to translate.
4. Send to ©Deepl.
5. Review and or amend automated translations.
6. Save translations to Moodle's DB (with the multilang {mlang XX} tags).

[Multi-Language Content (v2)](https://moodle.org/plugins/filter_multilang2) is a dependency of this plugin and will not work without it.

## Installation

Clone or [download](https://github.com/brunobaudry/moodle-local_deepler/releases) this plugin to ```/moodlewww/local/deepler``` and run through the database
upgrade process.

### Dependencies

You need a [©Deepl API](https://www.deepl.com/en/pro-api)  Free or Pro account.
[Multi-Language Content (v2)](https://moodle.org/plugins/filter_multilang2) is a dependency of this plugin and will not work without it.

## Configuration

### Permissions

Course Translator will extend Moodle with the ```local/deepler:edittranslations``` capability. Assign the capability to a new Translator role or add it to one of your
existing roles.

### Webservices

This plugin will add a ```local_deepler_update_translation``` web service for the translation page to perform ajax requests against.

### Admin

To configure the plugin, navigate to **Site Administration -> Plugins -> Local plugins -> Manage local plugins.** From this page you can configure ©Deepl settings, specify wether
you are using ©Deepl API Free or ©Deepl API Pro. Visit
the [©Deepl API page](https://developers.deepl.com/docs/getting-started/readme) to
signup for an api key that you can enter into local plugin settings.

#### Allow sub-languages to be mapped to their main

Currently Deepl doesn't support any. See https://developers.deepl.com/docs/resources/supported-languages.
The plugin gives you the possiblity to use these sub languages if your moodle install has them as if they were their parent language.
This setting is ticked by default.

If your installation has sub local language, for exemple de_ch, it will be considered as its main (de), else the plugin will display an "source lang unsupported" error page (
defaults to true)

#### Default value Escape LaTeX (in the courses translation page "Advanced Settings")

Set to true will check "escape LaTeX formulas", in the course translation form. This will have the effect to enable by default the Non translation of LaTeX formulas in course (when
set here to true).
Unchecking it here if your organisation rarely uses LaTeX formulas in the courses to slightly improve Deepler's performances,

#### Default value Escape PRE (in the courses translation page "Advanced Settings")

Do not send <pre>...</pre> to translation by default.

#### Minimum textfield size

Small text field are often limited in the database. The text content grows quite fast (plus the mlang tags) at each translation steps.
After translation, if the text is too big, the DB will through an error.

Size this here based on your main language properties and the number on languages your Moodle supports

![](pix/admin.png)

## Translating

To begin translating content, visit a course, open the course settings action menu, and then go to **Course Translator**.

![](pix/launch.png)

You will be sent to the translation page for the course.

### Advanced settings

#### Deepl API setting

There you can fine tune [©Deepl's commands](https://developers.deepl.com/docs).
Usually the default as set below should work fine with Moodle activity content.
*Glossaries' content have to be sent by other means than this plugin, but it is in our todo list to add an interface for that here.*

#### Other setting

##### Escape LaTeX and or PRE tags

Because Deepl is not very Good at that, iow we experienced that it would behave with inconsistency.
When this is set, Deepler will seek $$...$$ string, replace by a token, send to Deepl to translate and replace back the LaTeX formulas
upon return.
This setting's default in the editor can be set in the plugin [admin page](#admin).

![](pix/advanced_settings.png)

### Language selection

![](pix/source_target.png)

#### Source language

**The source language will be set to the actual Moodle language** selection which will automatically be set to **other**.
Changing the source language will act as if you'd change moodle's lang.
It is important, from a translation standpoint, to **select the source language from the language it was initially written** (and to stick with it).

When first translating your content the plugin will insert ```{mlang other}Your Content...{mlang}``` tags .
Please checkout the [mlang docs](https://moodle.org/plugins/filter_multilang2) to understand more.

*nb: indeed you cannot select the same target language as the source and vice versa.*

#### Target language

To change the language you want to translate to, choose a language from the **Target language {mlang XX}** dropdown.
Note: indeed you cannot translate from and to the same language so buttons and checkboxes would be disabled if so.

#### Unsupported

Language that are not suppoted by ©Deepl are checked at each session so if your Moodle instance have unsupported languages, you will not be able to select it.

### Header

![](pix/header.png)

### Filters

You can filter the rows to hide/show the ones that need to be translated as wished.
Clicking on the "Status" checkbox will select all visible.

These filter show/hide the textual content found in the course.

#### Up to date:

These are the content that are already translated and that no change were made in the source.

They will appear with the GREEN DOT indicator.

#### Needs update:

These are the textual contents that were never translated or that were modified after being translated.

They appear with the RED DOT indicator when they were never translated.

They appear with the ORANGE DOT indicator when they were already translated but the source text change since.

### Status indicator

![](pix/bullet_status.png)

Each row has a little colored dot to indicate a status:

- Red -> This field was never translated. (no {mlang xx} tag for the xx target language found)
- Green -> The filed was already translated and up to date (in the target language).
- Orange -> This field was translated but there were some updates made in the Database. (Needs a review)

### Translation process

#### Editing the source

It is not possible to edit the source content from this plugin's interface.
Nevertheless, clicking on the PENCIL icon will jump you to the regular place for you to do so.

![](pix/source_editing.png)

#### Reviewing past translations and multilang's tags

Clicking on the TRANSLATION icon will toggle the display of multilang tags and all available translations.

![](pix/multilang_toggle_off.png)
![](pix/multilang_toggle_on.png)

#### Images and medias.

The plugin will try to fetch and display embeded images. (this should be improved soon so that image are displayed in the source)
When not found it will highlight the alt text in yellow and italicised as seen above.

![](pix/multilang_toggle_img_off.png)
![](pix/multilang_toggle_img_on.png)

### Performing translations

![](pix/translation_process.png)

1. The text is not selected. No translation will occur. ( ... )
2. The text is selected but not sent to ©Deepl yet. (hourglass)
    1. By default, the main source language is selected. It will be stored within {mlang other}* tag.
    2. You set another language as source if your content had some content written in different lang. It will be stored within {mlang xx}* tag.
3. Translation is retrieved from ©Deepl and filed in the text editor. (floppy)
    1. Now the translator can review ©Deepl's work and amend the translation if necessary.
    2. Once happy with the content a click on the floppy button will save the text.
4. Translation is saved in the database, with the {mlang} filter surrounding it. (DB icon)

_Note* the following process when saving to the database _

The original content **has no MLANG tag** and the source lang is the main:

`{mlang other}SOURCE_CONTENT{mlang} {mlang target_lang}TRANSLATED_CONTENT{mlang}`

The original content **has no MLANG tag** and the source lang is different from the main:

`{mlang other}SOURCE_CONTENT{mlang} {mlang special_source_lang}SOURCE_CONTENT{mlang} {mlang target_lang}TRANSLATED_CONTENT{mlang}`

The original content **has already MLANG tag** and the source lang is the main:

`{mlang other}SOURCE_CONTENT{mlang} {mlang target_lang}TRANSLATED_CONTENT{mlang}`

The original content **has already MLANG tag** and the source lang is different from the main and there were **no {mlang other}**:

`{mlang other}SOURCE_CONTENT{mlang} {mlang special_source_lang}SOURCE_CONTENT{mlang} {mlang target_lang}TRANSLATED_CONTENT{mlang}`

The original content **has already MLANG tag** and the source lang is different from the main and there were **already a {mlang other**:

`{mlang other}ANOTHER_SOURCE{mlang} {mlang special_source_lang}SOURCE_CONTENT{mlang} {mlang target_lang}TRANSLATED_CONTENT{mlang}`

### Modules

![](pix/modules_activities.png)

Content's texts fields are displayed in the order of appearance of the course.
A module (course settings, topics etc.) separator corresponding to the activities grouping is displayed with its title,
underneath you can see it available for translation.

_Note that: for now, the naming of the instances as well ads the fields are the DB ones,
it will soon be replaced by the one used in Moodle's course layout for a better usability._

## User tour (inline tutorial)

You can install a [tour guide](https://github.com/brunobaudry/moodle-local_deepler/blob/main/tourguide/tour_export.json) to simplify your translators' trainings.

See moodle's instructions here : [User tours](https://docs.moodle.org/31/en/User_tours)

## WARNING

### Complex structures.

Activities/resources with complex sub content may not all work.
We currently plan to support all the core mods and would be happy to add more (send us a PR)
Book does, wiki too but beware when translating [[links]] as it will break the page link. (this should be fixed in future releas)

### Multi mlang xx tags inside a field

At this time, Course Translator does not have the ability to translate advanced usage of mlang in content. For example, this includes the use of multiple mlang tags spread
throughout content that utilize the same language._

**Note**: Still you can add untranslated content, after a first insertion of mlang tags, before and/or after, the parser should then leave them in place.

### Image display and image alt attributes

Currently, images are only displayed in the preview but not in the text editor. Instead, the alt attribute content is highlighted.
The Alt attribute is not sent to ©Deepl. This should be added in further improvement for better accessibility.

## Compatability

This plugin has been tested on Moodle 4.1+
php >= 8.1

Should work with the following editors:

- Plaintext
- Atto
- Tiny
- Marklar

It uses the default Moodle JS library and is tested with Boost and Classic.
So there could be incompatibilities with other themes.

## How does this plugin differs from Content Translation Manager and Content Translation Filter?

This plugin does not translate every string on your site. It is only meant for translating courses and it uses Moodle's built in multilingual features along with ```{mlang}``` to
translate your content. When you backup and restore courses, your translations will migrate with your content. Updating your source content will provide a "Update Needed" status
message on the course translation page.

## Future (todos)

- Machine translation API abstraction to use other services than ©Deepl.
- Display images all times.
- Translations versioning.
- Import glossaries.
- Multiple API key setting and user mapping.
- Document translation.

## Submit an issue

Please [submit issues here.](https://github.com/jamfire/moodle-local_deepler/issues)

## Changelog

See the [CHANGES.md](CHANGES.md) documentation.

## Contributing

See the [CONTRIBUTING.md](CONTRIBUTING.md) documentation.

## Fork

This is a fork of Jamfire's https://github.com/jamfire/moodle-local_coursetranslator which was left deprecated with https://github.com/jamfire/moodle-filter_autotranslate for
replacement.
Though going the filter way is most probably the best way for auto translation, we found useful to improve this one adding the necessary "revision" step as Machine translation will
never be 100% accurate specially in the context of knowledge transmission where accuracy is mandatory.
