<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Local Course Translator Strings
 *
 * @package    local_deepler
 * @copyright  2022 Kaleb Heitzman <kaleb@jamfire.io>
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/String_API
 */

defined('MOODLE_INTERNAL') || die();

// General strings.
$string['pluginname'] = 'Deepl Translator';
$string['deepler:edittranslations'] = 'Edit Translations';
$string['edittranslation'] = 'Edit Translation';

// DeepL strings.
$string['apikey'] = 'API Key for DeepL Translate';
$string['apikey_desc'] = 'Copy your api key from DeepL to use machine translation.';
$string['deeplpro'] = 'Use DeepL Pro?';
$string['deeplpro_desc'] = 'Enable this to use DeepL Pro instead of the free version of DeepL.';
$string['supported_languages'] =
        'bg,cs,da,de,el,en,es,et,fi,fr,hu,it,ja,lt,lv,nl,pl,pt,ro,ru,sk,sl,sv,zh'; // Do not change between translations.*/
// Template strings.
$string['t_contextDeepl'] = 'Course context ';
$string['t_deeplapidoc'] = 'see detail on deepl\'s documentation';
$string['t_contextDeeplPlaceholder'] =
        'Tell the translator (Deepl) about the context, to help it translate in a more contextual way... ';
$string['t_sourceLang'] = 'Source lang <em>{mlang other}</em>';
$string['t_select_target_language'] = 'Target language <em>{mlang {$a}}</em>';
$string['t_word_count'] = '{$a} words';
$string['t_char_count'] = '{$a} characters';
$string['t_word_count_sentence'] =
        'Total <span id="local_deepler__wc">0</span> words, <span id="local_deepler__wosc">0</span> characters (<span id="local_deepler__wsc">0</span> chars including spaces) Deepl\'s usage = <span id="local_deepler__used">0</span>/<span id="local_deepler__max">0</span>';
$string['t_warningsource'] =
        'Watch out ! The current source language &quot;{$a}&quot; is already as a multilang tag along side with the fallback tag &quot;OTHER&quot;. Note that both will be merge as the &quot;OTHER&quot; multilang tag.';
$string['t_char_count_spaces'] = '({$a} char including spaces)';
$string['t_autotranslate'] = 'Translate &rarr; {$a}';
$string['t_source_text'] = 'Source lang: {$a}';// Deprecated.
$string['t_special_source_text'] = 'Use a different source than "{$a}"';
$string['t_translation'] = 'Target lang: {$a}';// Deprecated.
$string['t_autosaved'] = 'Saved!'; // Deprecate.
$string['t_selectall'] = 'All';
$string['t_saveall'] = 'Save&nbsp;all';
$string['t_saveallexplain'] = 'Batch save to database all selected translations.';
$string['t_status'] = 'Status';
$string['t_other'] = 'Other (other)';
$string['t_multiplemlang'] =
        'This field is using advanced {mlang} usage. Please edit translation using standard Moodle editor or simplify to a single mlang tag per language.';// Deprecate.
$string['t_needsupdate'] = 'Needs update';
$string['t_uptodate'] = 'Up to date';
$string['t_nevertranslated'] = 'No \'{$a}\' translation yet';
$string['t_canttranslate'] = 'Cannot translate \'{$a}\' to \'{$a}\', please select a different target language';

$string['t_edit'] = 'Edit source in place';
$string['t_viewsource'] = 'Check multilingual content.';
$string['t_seeSetting'] = 'Advanced Deepl settings';
$string['t_splitsentences'] = 'Split sentences?';
$string['t_splitsentences_0'] = 'no splitting at all';
$string['t_splitsentences_1'] = 'splits on punctuation and on newlines';
$string['t_splitsentences_nonewlines'] = 'splits on punctuation only, ignoring newlines';
$string['t_preserveformatting'] = 'Preserve formatting';
$string['t_formality'] = 'Formality';
$string['t_formality_default'] = 'default';
$string['t_formality_less'] = 'less';
$string['t_formality_more'] = 'more';
$string['t_formality_prefer_more'] = 'prefer more';
$string['t_formality_prefer_less'] = 'prefer less';
$string['t_glossaryid'] = 'Glossary id';
$string['t_glossaryid_placeholder'] = 'Glossary id should you have one...';
$string['t_taghandling'] = 'Handle tags as : ';
$string['t_outlinedetection'] = 'XML Outline detection';
$string['t_tagsplaceholder'] = 'List all tags (separate tag with comma &quot;,&quot;)';
$string['t_nonsplittingtags'] = 'Non splitting tags';
$string['t_splittingtags'] = 'Splitting tags';
$string['t_ignoretags'] = 'Tags to ignore';
