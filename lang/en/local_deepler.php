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

$string['allowbeta'] = 'Allow beta languages.';
$string['allowbeta_desc'] = 'Tick this if you want to use what DeepL used to call "beta languages"
(marked with a star in their [supported languages](https://developers.deepl.com/docs/getting-started/supported-languages) page).
Those languages are not listed as available languages by
[DeepL API](https://developers.deepl.com/api-reference/languages/retrieve-supported-languages).';
$string['allowfallbackkey'] = 'Allow fallback key.';
$string['allowfallbackkey_desc'] =
        'Tick this if you want to allow users with no matching attributes to the mapped tokens, to use a default one.';
$string['apikeytitle'] = 'API Key for DeepL Translate';
$string['apikeytitle_desc'] = 'Copy your API key from DeepL to use machine translation.';
$string['badsettings'] = 'The DeepL setting seems to be incomplete, probably missing the API key.
Check with your Moodle admin.';
$string['breadcrumblength'] = 'Max length of breadcrumb\'s sub';
$string['breadcrumblength_desc'] =
        'Set it to limit the size of the breadcrumb\'s subs. Set it to zero should you want no limiting.';
$string['canttranslate'] = 'Cannot translate \'{$a}\' to \'{$a}\', please select a different target language';
$string['canttranslatesame'] = 'Both languages are the same:';
$string['contextdeepl'] = 'Course context ';
$string['contextdeepl_placeholder'] =
        'Tell the translator (DeepL) about the context, to help it translate in a more contextual way... (experimental)';
$string['cookieduration'] = 'Advanced setting storage duration';
$string['cookieduration_desc'] =
        'The advanced settings are saved (in a cookie) on a per course and language pair. Set duration value in days here.';
$string['deeplapidoc'] = 'see detail on deepl\'s documentation';
$string['deeplapidoctitle'] = 'DeepL\'s API settings';
$string['deeplapiexception'] = 'DeepL\'s API returned an error';
$string['deepler:edittranslations'] = 'Edit course translations in DeepL Translator';
$string['deeplprotitle'] = 'Use DeepL Pro?';
$string['deeplprotitle_desc'] = 'If enabled use DeepL Pro else the free version of DeepL API.';
$string['doselecttarget'] = 'Please select a target language.';
$string['editbutton'] = 'Edit source in place';
$string['errordbpartial'] = '{$a} of the fields NOT saved in the database. Check manually in place';
$string['errordbtitle'] = 'Database error';
$string['errortoolong'] = '(could be that the text is too long for the field... Check manually in place)';
$string['fieldmaxlengtha'] = '{$a} characters';
$string['fieldmaxlengthb'] = '(max {$a})';
$string['filters'] = 'Filters';
$string['formality'] = 'Formality';
$string['formalitydefault'] = 'default';
$string['formalityless'] = 'less';
$string['formalitymore'] = 'more';
$string['formalitypreferless'] = 'prefer less';
$string['formalityprefermore'] = 'prefer more';
$string['glossary:actions'] = 'Actions';
$string['glossary:deepl:link'] = 'DeepL API documentation';
$string['glossary:deeplid'] = 'UUID';
$string['glossary:delete:deeplissue:body'] = 'Could not delete the glossary: {$a}';
$string['glossary:delete:deeplissue:title'] = 'DeepL issue';
$string['glossary:delete:failed:body'] = 'Failed to delete the glossary: {$a}';
$string['glossary:delete:failed:title'] = 'Deletion failed';
$string['glossary:delete:idmissing:body'] = 'Failed to delete the glossary: {$a}';
$string['glossary:delete:idmissing:title'] = 'Missing ID';
$string['glossary:delete:invalidsesskey:body'] = 'Failed to delete the glossary: {$a}';
$string['glossary:delete:invalidsesskey:title'] = 'Invalid session key';
$string['glossary:delete:success:body'] = '{$a} has been removed!';
$string['glossary:delete:success:title'] = 'Deletion successful';
$string['glossary:id'] = 'Glossary id';
$string['glossary:id_placeholder'] = 'Glossary id should you have one...';
$string['glossary:lastused'] = 'Last time it was used';
$string['glossary:listempty'] =
        'There are no glossaries available with for language pair. Go to "DeepLer glossaries" in your user preferences to upload some.';
$string['glossary:manage:title'] = 'DeepLer glossaries';
$string['glossary:name'] = 'Name';
$string['glossary:nbentries'] = 'Entries';
$string['glossary:neverused'] = 'Never';
$string['glossary:notselected'] = 'No Glossary selected';
$string['glossary:pool'] = 'Pool';
$string['glossary:pool:admin'] = 'Admin owned';
$string['glossary:pool:title'] = 'Pool\'s glossaries';
$string['glossary:private:title'] = 'Private glossaries';
$string['glossary:public:title'] = 'Public glossaries';
$string['glossary:select'] = 'Select a glossary';
$string['glossary:selectplaceholder'] = 'Enter a glossary name';
$string['glossary:sourcelang'] = 'Source';
$string['glossary:targetlang'] = 'Target';
$string['glossary:upload'] = 'Upload';
$string['glossary:upload:btn'] = 'Create glossary';
$string['glossary:upload:deeplissue:body'] = 'Could not upload the glossary because: {$a}';
$string['glossary:upload:deeplissue:title'] = 'DeepL issue';
$string['glossary:upload:failed:body'] = 'Failed to upload the glossary because: {$a}';
$string['glossary:upload:failed:title'] = 'Upload error';
$string['glossary:upload:file:supported'] = 'Supported file types: CSV, TSV, XLSX, XLS, ODS';
$string['glossary:upload:fileerror:body'] = 'Failed to upload the glossary because: no file found';
$string['glossary:upload:fileerror:title'] = 'File error';
$string['glossary:upload:filetypeunsupported:body'] = 'Unsupported file type';
$string['glossary:upload:filetypeunsupported:title'] = '${a} is not valid. Please upload CSV, TSV, XLSX, XLS, or ODS.';
$string['glossary:upload:helpmodal:col1'] = "##### FR > ES (file naming conventions)

**File: 'litérature\_fr-es.csv**'

belle      , hermosa

delicieux  , exquisito

mouse      , mouse

##### ES > FR (using headers)
**File: 'litérature-es-fr.csv**'

ES   , FR

hermosa   , belle

exquisito , delicieux

mouse     , mouse  ";
$string['glossary:upload:helpmodal:col2'] = "##### File naming convention examples :

**Expl OK**

*   私の用語集**_en-ja.xls**
*   Tech\_jargon**\_FR-ES.CSV**
*   myGlossary**-**en-fr.csv (ok only if it has EN FR header row)

**Expl NOT OK (when not using the headers)**

*   myGlossary**-**en-fr.csv (here it will search for langs in the header row)
*   Tech\_jargon\_**FRA-SPA**.ods (3 letter codes, but will not search for the header row)
*   myHistoricalGlo_fr-es.**txt** (unsupported extension)
";
$string['glossary:upload:helpmodal:intro'] = "#### Rules

Bidirectional (v3) glossaries are not yet allowed.

The spreadsheet must contain a single sheet with two columns and either (1):

- Valid 2-letter code column headers (expl EN and JA) in upper or lowercase letters.
- Valid file name following the conventions below.

##### File naming conventions:

1.  Rename the GLOSSARYNAME\_SOURCE-TARGET.EXTENSION
2.  Name it meaningfully but not too long (for a nice display in tables).
3.  No header in the csv file.
4.  SOURCE and TARGET in the file name must be 2 character language code.

(1)_The file naming has precedence over headers.
So if you want to use the header way but add the lang pairs in the file name,
 make sure the name has no suffixe like \_SOURCE-TARGET, avoid using a '\_' folowed by a '-'.
 Do something like GLOSSARYNAME-SOURCE-TARGET.EXTENSION for example._
";
$string['glossary:upload:helpmodal:title'] = 'Upload a simple Source|Target 2 columns spreadsheet';
$string['glossary:upload:invalidsesskey:body'] = 'Failed to upload the glossary because: {$a}';
$string['glossary:upload:invalidsesskey:title'] = 'Invalid session key';
$string['glossary:upload:langpair:notresolved:body'] = 'Neither in the file title, nor in the headers.';
$string['glossary:upload:langpair:notresolved:title'] = 'Could not resolve the source-target language pair';
$string['glossary:upload:sourcenotsupported:body'] = '{$a} is not a supported source language';
$string['glossary:upload:sourcenotsupported:title'] = 'DeepL: not supported source language';
$string['glossary:upload:success:body'] = '{$a} has been uploaded!';
$string['glossary:upload:success:title'] = 'Upload successful';
$string['glossary:upload:suffixerror:body'] = 'Failed to upload the glossary because: {$a}, please check the instructions.';
$string['glossary:upload:suffixerror:title'] = 'Incorrect suffix';
$string['glossary:upload:targetnotsupported:body'] = '{$a} is not a supported target language';
$string['glossary:upload:targetnotsupported:title'] = 'DeepL: not supported target language';
$string['glossary:upload:unknownerror:body'] = 'Failed to upload the glossary because: {$a}, please check the instructions.';
$string['glossary:upload:unknownerror:title'] = 'Incorrect suffix';
$string['glossary:visibility'] = 'Visibility';
$string['glossary:visibility:pool'] = 'Pool';
$string['glossary:visibility:private'] = 'Private';
$string['glossary:visibility:public'] = 'Public';
$string['hideiframes'] = 'Render embed iframes in source text.';
$string['hideiframesadmin'] = 'Select this if you want rendering embed iframes be the default.';
$string['hideiframesadmin_desc'] =
        'Sometimes iframes can overlap the user interface, or alter the browser\'s performances.';
$string['ignoretags'] = 'Tags to ignore';
$string['keepselected'] = 'Keep selected';
$string['latexeascape'] = 'Escape LaTeX (do not send $$LaTeXFormulas$$ to translation)';
$string['latexescapeadmin'] = 'Default value Escape LaTeX
(in the courses translation page "Advanced Settings")';
$string['latexescapeadmin_desc'] = 'If enabled, the plugin will set "escape LaTeX formulas" to true in the course translation form (advanced settings).
Disable it here if your organisation rarely uses LaTeX formulas in the courses to slightly improve DeepLer\'s performances.';
$string['missingmainapikey'] = 'First add a default API key to use this feature';
$string['modeltpreferqualityoptimized'] = 'prefer quality optimized';
$string['modeltype'] = 'Model';
$string['modeltypelatencyoptimized'] = 'latency optimized';
$string['modeltypequalityoptimized'] = 'quality optimized';
$string['module:select:label'] = 'Select activities';
$string['moduleall'] = 'ALL ACTIVITIES';
$string['needsupdate'] = 'Needs update';
$string['neverrephrased'] = 'This text was never improved';
$string['nevertranslated'] = 'No \'{$a}\' translation yet';
$string['nodeeplpapi'] =
        ':-( Cannot connect to DeepL API. <br/>Check with your admin. Looks like there is a network issue.';
$string['nonsplittingtags'] = 'Non splitting tags';
$string['notsupportedsource'] = 'The source language you are in is not supported by DeepL.';
$string['onomatopoeia'] = 'Ouch !!!';
$string['othersettingstitle'] = 'Other settings';
$string['outlinedetection'] = 'XML Outline detection';
$string['pluginname'] = 'DeepL Translator';
$string['pluginversion'] = 'Current version';
$string['preescape'] = 'Escape PRE html tag ';
$string['preescapeadmin'] = 'Escape PRE html tag ';
$string['preescapeadmin_desc'] = 'If enabled, &lt;pre&gt;...&lt;/pre&gt; content will not be sent to translation';
$string['preserveformatting'] = 'Preserve formatting';
$string['privacy:metadata'] = 'The DeepLer plugin does not store any personal data.';
$string['rephraseinfos'] =
        'Text improvement is beta and currently supported for the following target languages: <strong>{$a}</strong>.';
$string['rephraseoptionsinfos'] =
        'The options for tone or writing style only  supported for <strong>de</strong>, <strong>en-GB</strong>, <strong>en-US</strong>. Using "default" as value behaves the same as not sending a tone nor a writing style.';
$string['rephrasetitle'] = '{$a}Rephrase settings';
$string['saveall'] = 'Save&nbsp;all';
$string['saveallexplain'] = 'Batch save to database all selected translations.';
$string['saveallmodalbody'] = '<div class="spinner-border text-primary" role="status"><span class="sr-only">Saving...</span>\n</div>
<p>Please wait ...<br/>When all fields are saved in the database,<br/>I will automatically close</p>
<p>If you are impatient, and want to close this window,
<br/>make sure all selected translation\'s statuses are <i class="fa fa-database" aria-hidden="true"></i></p>';
$string['saveallmodaltitle'] = 'Saving translations to the database';
$string['scannedfieldsize'] = 'Minimum textfield size';
$string['scannedfieldsize_desc'] = 'Small text field are often limited in the database. The text content grows quite fast
 (plus the mlang tags) at each translation steps.
 After translation, if the text is too big, the DB will through an error. Size this here based on your main language properties and
 the number on languages your Moodle supports';
$string['section:coursesettings'] = 'Course settings';
$string['section:select:label'] = 'Select sections';
$string['sectionall'] = 'ALL SECTIONS';
$string['seesetting'] = 'Advanced settings';
$string['selectall'] = 'All';
$string['selecttargetlanguage'] = 'Target language <em>{mlang {$a}}</em>';
$string['showhiddenforstudents'] = 'Show "{$a}"';
$string['sourcelang'] =
        'Source lang <em>{mlang other} </em>';
$string['specialsourcetext'] = 'Use a different source than "{$a}"';
$string['splitsentences'] = 'Split sentences?';
$string['splitsentences0'] = 'no splitting at all';
$string['splitsentences1'] = 'splits on punctuation and on newlines';
$string['splitsentencesnonewlines'] = 'splits on punctuation only, ignoring newlines';
$string['splittingtags'] = 'Splitting tags';
$string['statusfailed'] = 'Failed';
$string['statussaved'] = 'Saved';
$string['statussuccess'] = 'Succeeded';
$string['statustosave'] = 'Save this';
$string['statustotranslate'] = 'Ready to translate';
$string['statuswait'] = 'Not selected';
$string['taghandling'] = 'Handle tags as : ';
$string['tagsplaceholder'] = 'List all tags (separate tag with comma &quot;,&quot;)';
$string['targetcompatibleexplain'] = 'Chose a target language to save in the {mlang xx}.';
$string['tokenactions'] = 'Actions';
$string['tokenadd'] = 'Add token map';
$string['tokenaddtoken'] = 'Add Token';
$string['tokenadminpagedescription'] =
        'Select a user attribute, give it a filter (you can use *,% or _ wildcards) and match that to a token.';
$string['tokenattribute'] = 'User Attribute';
$string['tokenchoose'] = 'Select an attribute';
$string['tokendeletetoken'] = 'Delete Token';
$string['tokenedittoken'] = 'Edit Token';
$string['tokenerror_invaliduuid'] = 'Invalid token format';
$string['tokenerror_noattribute'] = 'You must select an attribute to filter.';
$string['tokenerror_nofilter'] = 'You must ad a value to filter.';
$string['tokenfiltervalue'] = 'Add a filter value';
$string['tokengobacktosettings'] = 'Manage DeepLer\'s settings';
$string['tokengototokenmanager'] = 'Manage DeepL\'s tokens';
$string['tokenmanager'] = 'DeepLer Token manager';
$string['tokenmanager_desc'] =
        'Here you can map your DeepL api keys to user\'s attributes in order to create pools of translators. This can be useful should you need to measure and control consumption for each API key.';
$string['tokennotokens'] = 'No tokens found.';
$string['tokensettings'] = 'API Token Settings';
$string['tokentabletitle'] = 'Token table';
$string['tokentoken'] = 'Token';
$string['tokentokenmanager_addnew'] = 'Add a new token filter';
$string['tokentokenmanager_mappings'] = 'Token table';
$string['tokenvaluefilter'] = 'Value Filter';
$string['toneconfident'] = 'Confident tone';
$string['tonediplomatic'] = 'Diplomatic tone';
$string['toneenthusiastic'] = 'Enthusiastic tone';
$string['tonefriendly'] = 'Friendly tone';
$string['toneorstyle'] = 'Changes the tone or writing style of your improvements:';
$string['tour_advancedsettings00'] =
        'Click here to see how you can fine tune the DeepL’s behaviour.<br/><br/>Click now to get a guided tour of the features.';
$string['tour_advancedsettings00title'] = 'DeepL’s advanced settings';
$string['tour_advancedsettings01formality'] = '<p>Sets whether the translated text should lean towards formal or informal language.
This feature currently only works for target languages <em>DE</em> (<strong>German</strong>), <em>FR</em> (<strong>French</strong>), <em>IT</em>
(<strong>Italian</strong>), <em>ES</em> (Spanish), <em>NL</em> (Dutch), <em>PL</em> (Polish), <em>PT-BR</em> and <em>PT-PT</em> (Portuguese),
<em>JA</em> (Japanese), and <em>RU</em> (Russian).
Learn more about the plain\/polite feature for Japanese <a
href="https://support.deepl.com/hc/en-us/articles/6306700061852-About-the-plain-polite-feature-in-Japanese">here</a>.
Setting this parameter with a target language that does not support formality will fail, unless one of the&nbsp;
<em>prefer_...</em> options are used. Possible options are:</p>
<ul><li><em>default</em> (default)</li>
<li><em>more</em> - for a more formal language</li>
<li><em>less</em> - for a more informal language</li>
<li><em>prefer more</em> - for a more formal language if available, otherwise fallback to default formality</li>
<li><em>prefer less</em> - for a more informal language if available, otherwise fallback to default formality</li></ul>';
$string['tour_advancedsettings01formalitytitle'] = 'Formality management';
$string['tour_advancedsettings02split'] = '<p>Sets whether the translation engine should first split the input into sentences.
When <em>splits on punctuation and on newlines</em>, the engine splits on punctuation and on newlines.</p>
<p>When <em>splits on punctuation only, ignoring newlines</em>, the engine splits on punctuation only, ignoring newlines.</p>';
$string['tour_advancedsettings02splittitle'] = 'Manage how sentences are split by line';
$string['tour_advancedsettings03formating'] = '<p>Sets whether the translation engine should respect the original formatting,
even if it would usually correct some aspects.</p>
<p>The formatting aspects affected by this setting include:</p>
<ul><li>Punctuation at the beginning and end of the sentence</li><li>Upper/lower case at the beginning of the sentence</li></ul>';
$string['tour_advancedsettings03formatingtitle'] = 'Manage formating';
$string['tour_advancedsettings04glossary'] = '<p>Specify the glossary to use for the translation.</p>
<p><em>(Glossaries have to be uploaded via the DeepL API. This is not available yet with this plugin. See with your IT).</em></p>';
$string['tour_advancedsettings04glossarytitle'] = 'Glossary';
$string['tour_advancedsettings05context'] = '<p>This additional context can potentially improve translation quality when translating short,
low-context source texts.</p><p>The <em>context</em> parameter is an <strong>alpha feature</strong>.</p>
<p>So try to add some context if you feel the translated results could be improved, but you should not rely on it.</p>';
$string['tour_advancedsettings05contexttitle'] =
        'Contextual information that can influence a translation but is not translated itself.';
$string['tour_advancedsettings06tag'] = '<p>Sets which kind of tags should be handled.</p>
<p>By default, the translation engine does not take tags into account.</p>
<p>By setting the <em>tag handling</em> parameter to either <em>xml</em> or <em>html</em>,
the API will process the markup input by extracting the text out of the structure, splitting it into individual sentences,
translating them, and placing them back into the respective markup structure.</p>';
$string['tour_advancedsettings06tagtitle'] = 'Tag handling';
$string['tour_advancedsettings07outline'] = '<p>The automatic detection of the XML structure won’t yield best results in all XML files.
You can disable this automatic mechanism altogether by setting the <em>outline detection</em> parameter to <em>unchecked</em>
and selecting the tags that should be considered structure tags. This will split sentences using the <em>splitting tags</em> parameter.</p>';
$string['tour_advancedsettings07outlinetitle'] = 'How outline is detected in XML';
$string['tour_advancedsettings08skiptag'] = '<p>Comma-separated list of XML or HTML tags that indicate text not to be translated.</p>
<p>To ensure that elements in the original text are not altered in translation (e.g. trademarks or product names).</p>
<p>Expl: adding "x" in the list :</p><p>Request:<em> Please open the page &lt;x&gt;Settings&lt;/x&gt; to configure your system.</em></p>
<p>Response:<em>Bitte öffnen Sie die Seite &lt;x&gt;Settings&lt;/x&gt; um Ihr System zu konfigurieren.</em></p>
<p>In HTML you can also use the <code><strong>translate="no"</strong></code> attribute :</p>
<p><code>  &lt;body&gt;</code><br /><code>    &lt;h1&gt;My First Heading&lt;/h1&gt;</code><br /><code>    &lt;p
<strong>translate="no"</strong>&gt;This will not be translated.&lt;/p&gt;</code><br /><code>  &lt;/body&gt;</code></p>
<p></p>';
$string['tour_advancedsettings08skiptagtitle'] =
        'Skip translating certain tags’ content.';
$string['tour_advancedsettings09splittag'] = '<p>Comma-separated list of XML or HTML tags which never split sentences.</p>';
$string['tour_advancedsettings09splittagtitle'] =
        'Tags that should not be considered to split the sentences.';
$string['tour_advancedsettings101other'] =
        '<p>Here you tell the plugin to avoid translating LaTeX strings ($$...$$) and or PRE HTML Tags.</p>';
$string['tour_advancedsettings101othertitle'] =
        'Enabling LaTeX and/or PRE tag escaping';
$string['tour_advancedsettings10splittag'] = '<p>Comma-separated list of XML or HTML tags which always cause splits.</p>';
$string['tour_advancedsettings10splittagtitle'] =
        'Tag that will split the text into sentences.';
$string['tour_advancedsettings11sourcelang'] = '<p>The source lang is the language in which the course was written.
It is best practice to keep the same language throughout the whole course.</p>';
$string['tour_advancedsettings11sourcelangtitle'] =
        'Source lang';
$string['tour_advancedsettings12targetlang'] = '<p>The target language is the one you will ask DeepL to return.</p>
<p>Obviuosly if you select the same language as the source the translation button is deactivated.</p>';
$string['tour_advancedsettings12targetlangtitle'] =
        'Target language';
$string['tour_advancedsettings13filters'] = '<p>These filter show/hide the textual content found in the course.</p>
<p><strong>Up to date:<br /></strong></p>
<p>These are the content that are already translated and that no change were made in the source.</p>
<p>They will appear with the <span class="badge badge-pill badge-success"> </span> indicator. </p>
<p><strong>Needs update:<br /></strong></p>
<p>These are the textual contents that were never translated or that were modified after being translated.</p>
<p>They appear with the <span class="badge badge-pill badge-danger"> </span> indicator when they were never translated. </p>
<p>They appear with the <span class="badge badge-pill badge-warning"> </span>
indicator when they were already translated but the source text change since.</p>';
$string['tour_advancedsettings13filterstitle'] =
        'Translation status filters';
$string['tour_advancedsettings14filters'] = '<p>Clicking here selects all visible content to be sent for translation.</p>';
$string['tour_advancedsettings14filterstitle'] =
        'Select All';
$string['tour_advancedsettings15filters'] =
        '<p>Real time status of the planned and the actual consumption of DeepL’s service (for the current month).</p>';
$string['tour_advancedsettings15filterstitle'] =
        'DeepL API consumption status';
$string['tour_advancedsettings16sendtodeepl'] =
        '<p>Clinkg this button will send all selected texts to DeepL and feed it in the editors.</p>
<p>At least one selection is needed to ctivate it.</p>';
$string['tour_advancedsettings16sendtodeepltitle'] =
        'Send to DeepL';
$string['tour_advancedsettings17statusbullet'] =
        '<p>This indicates the translation status with 3 color code.</p>
<p><span class="badge badge-pill badge-danger"> </span> This text was never translated.</p>
<p><span class="badge badge-pill badge-warning"> </span> This text was translated but there was a change made in the database since.</p>
<p><span class="badge badge-pill badge-success"> </span> This text was already translated and up to date.</p>
<p><span class="badge badge-pill badge-dark"> </span> Cannot get the translation status since <em>source</em> and <em>target</em> lang are the same.</p>';
$string['tour_advancedsettings17statusbullettitle'] =
        'Translation status bullet icon.';
$string['tour_advancedsettings18selection'] =
        '<p>To send a content to DeepL to be translated, you would need to tick that checkbox.</p>';
$string['tour_advancedsettings18selectiontitle'] =
        'Selection checkbox';
$string['tour_advancedsettings19editsource'] =
        '<p><span class="p-1 btn btn-sm btn-outline-info"><i class="fa fa-pencil"> </i>
</span> Clicking on the pencil will jump to the editor in the Moodle course.</p>
<p>Should you have revisions of the source, or want to make any change, as you cannot change the source from here.</p>';
$string['tour_advancedsettings19editsourcetitle'] =
        'Edit source in place.';
$string['tour_advancedsettings20togglemultilang'] =
        '<p>When translations {mlang} tags are present, this button appears.</p>
<p><i class="fa fa-language"></i></p>
<p>Click on it to toggle the content and see what was already translated. If the current selected source language is found in the
MLANG tags then this is displayed in red to alert you that the tag will be overriden.</p>';
$string['tour_advancedsettings20togglemultilangtitle'] =
        'Toggle mutlilang content.';
$string['tour_advancedsettings21secondsource'] =
        'You can choose a secondary source for specific content. <br/>If there is yet no OTHER lang tag, it will save the source in its language plus OTHER.';
$string['tour_advancedsettings21secondsourcetitle'] =
        'Secondary source language.';
$string['tour_advancedsettings22process'] =
        '<p>When a text content is not selected and no translation was requested. <i class="fa fa-ellipsis-h"></i> is shown.</p>
<p><i class="fa fa-hourglass-start"></i> is displayed when you selected it and it is waiting for you to press the "Translate" button to send it to DeepL.</p>
<p><i class="fa fa-floppy-o"></i> will display after the text was fed back in the adjacent text editor.<br />
You can review the translated content, make some changes then press the icon to actually save it in the database. <br /><br />
<em>Note</em> that you can also save to the database in batch by clicking on the save all floating button below. <br />
Should you want to save all tranlsated text but leave some to review later, you can uncheck it on the left so that it will be skiped when saving all.</p>
<p>Once a text is saved this icon will display <i class="fa-solid fa-database"></i></p>';
$string['tour_advancedsettings22processtitle'] =
        'Translation process indicator (far right)';
$string['tour_advancedsettings23saveall'] =
        '<p>When translations are retrieved from DeepL, they are not automatically saved to the database.</p>
<p>This to ensure the basics of translation, that a review is made before being stored and automatically dispatched to the public.</p>
<p>So you can either save them one by one or by clicking on the save all button.</p>
<p>If there are some translations that you do not want save in the batch, just unselect them before clicking "save all"</p>';
$string['tour_advancedsettings23savealltitle'] =
        'Save all translations to database.';
$string['translate:modal:body'] = 'It might take a little while, if you have a lot of content.';
$string['translate:modal:title'] = 'Called DeepL';
$string['translatebutton'] = 'Translate &rarr; {$a}';
$string['translateexplain'] = 'Translate all selected rows to {$a}';
$string['translationdisabled'] = 'Translation is disabled because this is used as a link in database';
$string['uptodate'] = 'Up to date';
$string['viewsource'] = 'Check multilingual content.';
$string['viewsourcedisabled'] = 'No multilingual content yet.';
$string['warningsource'] =
        'Watch out ! The current source language &quot;{$a}&quot; is already as a multilang tag along side with the fallback tag &quot;OTHER&quot;. Note that both will be merge as the &quot;OTHER&quot; multilang tag.';
$string['wordcountsentence'] =
        'Total <span id="local_deepler__wc">0</span> words, <span id="local_deepler__wosc">0</span> characters (<span id="local_deepler__wsc">0</span> chars including spaces). DeepL\'s&nbsp;usage&nbsp;= <span id="local_deepler__used">0</span>/<span id="local_deepler__max">0</span>';
$string['writingstyleacademic'] = 'Academic writing style';
$string['writingstylebusiness'] = 'Business writing style';
$string['writingstylecasual'] = 'Casual writing style';
$string['writingstylesimple'] = 'Simple writing style';
