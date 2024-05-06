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
 * Local Course Translator Strings.
 *
 * @package    local_deepler
 * @copyright  2022 Kaleb Heitzman <kaleb@jamfire.io>
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/String_API
 */
defined('MOODLE_INTERNAL') || die();
$string['apikeytitle'] = 'API Key for DeepL Translate';
$string['apikeytitle_desc'] = 'Copy your api key from DeepL to use machine translation.';
$string['canttranslate'] = 'Cannot translate \'{$a}\' to \'{$a}\', please select a different target language';
$string['contextdeepl'] = 'Course context ';
$string['contextdeepl_placeholder'] =
        'Tell the translator (Deepl) about the context, to help it translate in a more contextual way... ';
$string['deeplapidoc'] = 'see detail on deepl\'s documentation';
$string['deeplprotitle'] = 'Use DeepL Pro?';
$string['deeplprotitle_desc'] = 'Enable this to use DeepL Pro instead of the free version of DeepL.';
$string['editbutton'] = 'Edit source in place';
$string['formality'] = 'Formality';
$string['formalitydefault'] = 'default';
$string['formalityless'] = 'less';
$string['formalitymore'] = 'more';
$string['formalitypreferless'] = 'prefer less';
$string['formalityprefermore'] = 'prefer more';
$string['glossaryid'] = 'Glossary id';
$string['glossaryid_placeholder'] = 'Glossary id should you have one...';
$string['ignoretags'] = 'Tags to ignore';
$string['needsupdate'] = 'Needs update';
$string['nevertranslated'] = 'No \'{$a}\' translation yet';
$string['nodeeplpapi'] =
        'Cannot connect to Deepl API. Check with your admin. Either API key is missing, or there is a network issue';
$string['nonsplittingtags'] = 'Non splitting tags';
$string['outlinedetection'] = 'XML Outline detection';
$string['pluginname'] = 'Deepl Translator';
$string['preserveformatting'] = 'Preserve formatting';
$string['saveall'] = 'Save&nbsp;all';
$string['saveallexplain'] = 'Batch save to database all selected translations.';
$string['saveallmodalbody'] = '<div class="spinner-border text-primary" role="status">' .
        '  <span class="sr-only">Saving...</span>\n' .
        '</div>' .
        '<p>Please wait ...<br/>When all fields are saved in the database,<br/>I will automatically close</p>' .
        '<p>If you are impatient, and want to close this window,<br/>make sure all selected transaltion\'s statuses are ' .
        '<i class="fa fa-database" aria-hidden="true"></i></p>';
$string['saveallmodaltitle'] = 'Saving translations to the database';
$string['seesetting'] = 'Advanced Deepl settings';
$string['selectall'] = 'All';
$string['selecttargetlanguage'] = 'Target language <em>{mlang {$a}}</em>';
$string['sourcelang'] = 'Source lang <em>{mlang other}</em>';
$string['specialsourcetext'] = 'Use a different source than "{$a}"';
$string['splitsentences'] = 'Split sentences?';
$string['splitsentences0'] = 'no splitting at all';
$string['splitsentences1'] = 'splits on punctuation and on newlines';
$string['splitsentencesnonewlines'] = 'splits on punctuation only, ignoring newlines';
$string['splittingtags'] = 'Splitting tags';
$string['taghandling'] = 'Handle tags as : ';
$string['tagsplaceholder'] = 'List all tags (separate tag with comma &quot;,&quot;)';
$string['translatebutton'] = 'Translate &rarr; {$a}';
$string['uptodate'] = 'Up to date';
$string['viewsource'] = 'Check multilingual content.';
$string['viewsourcedisabled'] = 'No multilingual content yet.';
$string['warningsource'] =
        'Watch out ! The current source language &quot;{$a}&quot; is already as a multilang tag along side with the fallback tag &quot;OTHER&quot;. Note that both will be merge as the &quot;OTHER&quot; multilang tag.';
$string['wordcountsentence'] =
        'Total <span id="local_deepler__wc">0</span> words, <span id="local_deepler__wosc">0</span> characters (<span id="local_deepler__wsc">0</span> chars including spaces) Deepl\'s usage = <span id="local_deepler__used">0</span>/<span id="local_deepler__max">0</span>';
