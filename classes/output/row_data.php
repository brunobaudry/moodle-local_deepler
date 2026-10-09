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

namespace local_deepler\output;
defined('MOODLE_INTERNAL') || die();
use core_filters\text_filter;
use local_deepler\editor\MoodleQuickForm_cteditor;
use local_deepler\local\data\field;
use local_deepler\local\data\multilanger;
use local_deepler\local\services\lang_helper;
use renderable;
use renderer_base;
use templatable;

global $CFG;
require_once("$CFG->libdir/form/editor.php");
require_once("$CFG->dirroot/local/deepler/classes/editor/MoodleQuickForm_cteditor.php");

/**
 * Row page renderables
 *
 * @package    local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class row_data extends translate_data implements renderable, templatable {
    /**
     * @var \local_deepler\local\data\field
     */
    private field $field;
    /**
     * Construct.
     *
     * @param \local_deepler\local\data\field $field
     * @param \local_deepler\local\services\lang_helper $languagepack
     * @param \local_deepler\output\Multilang2TextFilter|\core_filters\text_filter $mlangfilter
     * @param string $editor
     */
    public function __construct(
        field $field,
        lang_helper $languagepack,
        Multilang2TextFilter|text_filter $mlangfilter,
        string $editor
    ) {
        parent::__construct($languagepack, $mlangfilter, $editor);
        $this->field = $field;
    }

    /**
     * Data for field row.
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return \stdClass|array
     * @throws \coding_exception
     */
    public function export_for_template(renderer_base $output) {
        $key = $this->field->getkey();
        $keyid = $this->field->getkeyid();
        $iseditable = $this->field->iseditable();
        $cssclass = $iseditable ? '' : 'alert alert-info text-muted';

        $tneeded = $this->field->get_status()->istranslationneeded();
        $status = $tneeded ? 'needsupdate' : 'updated';

        // Hacky Special cases where the content is a db key (should never be translated).
        $isdbkey = str_contains($this->field->get_table(), 'wiki_pages') && $this->field->get_tablefield() === 'title';
        $currentlang = $this->languagepack->currentlang;
        $fieldtext = $this->field->get_text();
        $trimedtext = trim($fieldtext);

        [$buttonclass, $titlestring] = $this->get_translation_button_info($tneeded, $fieldtext);
        [$multilangdisabled, $badgeclass, $multilangtitlestring] = $this->get_multilang_info($fieldtext, $currentlang);

        $fieldformat = $this->field->get_format();
        // Non editable or plain text (format 0) fields may hold raw HTML fragments (e.g. mod_data templates):
        // never inject them unescaped or they would break the page layout.
        $escapedisplay = !$iseditable || $fieldformat === 0;
        $mlangfilteredtext = $this->mlangfilter->filter($this->field->get_displaytext());
        if ($escapedisplay) {
            $mlangfilteredtext = s($mlangfilteredtext);
        }

        $istiny = $this->editor === 'tiny';
        $plaintextinput = $fieldformat === 0;
        // Non Tiny users (atto, textarea, marklar...) need the editor form element rendered server side.
        $cteditor = ($iseditable && !$istiny && !$plaintextinput) ? $this->rendercteditor($key, $fieldformat) : '';

        [$warnmaxlength, $warntextcolor, $maxlength] = $this->get_maxlength_warning_info(strlen($trimedtext));

        return [
            'badgeclass' => $badgeclass,
            'buttonclass' => $buttonclass,
            'cmid' => $this->field->get_cmid(),
            'cssclass' => $cssclass,
            'fieldformat' => $fieldformat,
            'fieldtranslation' => multilanger::findfieldstring($this->field),
            'flagandkey' => "$isdbkey$key",
            'id' => $this->field->get_id(),
            'iseditable' => $iseditable,
            'istiny' => $istiny,
            'cteditor' => $cteditor,
            'key' => $key,
            'keyid' => $keyid,
            'mlangfiltered' => $mlangfilteredtext,
            'multilangdisabled' => $multilangdisabled,
            'multilangtitlestring' => $multilangtitlestring,
            'plaintextinput' => $plaintextinput,
            'escapedisplay' => $escapedisplay,
            'rawsourcetext' => base64_encode($this->mlangfilter->filter($fieldtext) ?? ''),
            'rawunfilterdtext' => base64_encode($trimedtext),
            'rowtitle' => $isdbkey ? get_string('translationdisabled', 'local_deepler') : '',
            'selecttitle' => get_string('specialsourcetext', 'local_deepler', strtoupper($currentlang)),
            'sourceoptions' => $this->languagepack->preparesourcesoptionlangs(),
            'status' => $iseditable ? $status : 'local_deepler/disabled',
            'table' => $this->field->get_table(),
            'tablefield' => $this->field->get_tablefield(),
            'tid' => $this->field->get_tid(),
            'titlestring' => htmlentities($titlestring, ENT_HTML5),
            'trimedtext' => $trimedtext,
            'warnmaxlength' => $warnmaxlength,
            'warntextcolor' => $warntextcolor,
            'maxlength' => $maxlength,
            'totalchar' => strlen($trimedtext),
        ];
    }

    /**
     * Determine translation button class and tooltip title string.
     *
     * @param bool $tneeded
     * @param string $fieldtext
     * @return array{0: string, 1: string} [buttonclass, titlestring]
     * @throws \coding_exception
     */
    private function get_translation_button_info(bool $tneeded, string $fieldtext): array {
        $canrephrase = $this->languagepack->get_canimprove();
        $sametargetassource = $this->languagepack->isrephrase();
        $targetlang = $this->languagepack->targetlang;

        if ((!$canrephrase && $sametargetassource) || $targetlang === '') {
            $titlestring = get_string($canrephrase ? 'doselecttarget' : 'canttranslate', 'local_deepler', $targetlang);
            return ['badge-dark', $titlestring];
        }

        if ($tneeded) {
            return $this->get_needed_translation_button_info($fieldtext, $targetlang, $canrephrase && $sametargetassource);
        }

        return ['badge-success', get_string('uptodate', 'local_deepler')];
    }

    /**
     * Get button styling for fields that need translation updates.
     *
     * @param string $fieldtext
     * @param string $targetlang
     * @param bool $isrephrase
     * @return array{0: string, 1: string}
     * @throws \coding_exception
     */
    private function get_needed_translation_button_info(string $fieldtext, string $targetlang, bool $isrephrase): array {
        if (str_contains($fieldtext, "{mlang " . $targetlang)) {
            return ['badge-warning', get_string('needsupdate', 'local_deepler')];
        }

        $buttonclass = $isrephrase ? 'badge-primary' : 'badge-danger';
        $titlestring = get_string($isrephrase ? 'neverrephrased' : 'nevertranslated', 'local_deepler', $targetlang);
        return [$buttonclass, $titlestring];
    }

    /**
     * Compute multilang source inspection button attributes.
     *
     * @param string $fieldtext
     * @param string $currentlang
     * @return array{0: string, 1: string, 2: string} [multilangdisabled, badgeclass, multilangtitlestring]
     * @throws \coding_exception
     */
    private function get_multilang_info(string $fieldtext, string $currentlang): array {
        $multilanger = new multilanger($fieldtext);
        if (!$multilanger->has_multilangs()) {
            return ['disabled', 'secondary', get_string('viewsourcedisabled', 'local_deepler')];
        }

        if ($multilanger->has_multilandcode_and_others($currentlang)) {
            $badgeclass = 'danger';
            $titlestring = get_string('warningsource', 'local_deepler', strtoupper($currentlang));
        } else {
            $badgeclass = 'info';
            $titlestring = get_string('viewsource', 'local_deepler');
        }

        $titlestring .= ' (' . implode(', ', $multilanger->findmlangcodes()) . ')';
        return ['', $badgeclass, $titlestring];
    }

    /**
     * Determine maxlength warning status and styling.
     *
     * @param int $totalschar
     * @return array{0: bool, 1: string, 2: int|null} [warnmaxlength, warntextcolor, maxlength]
     */
    private function get_maxlength_warning_info(int $totalschar): array {
        $maxlength = $this->field->get_maxlength() ?? -1;
        if ($maxlength <= 0) {
            return [false, 'warning', null];
        }

        $charratio = $totalschar / $maxlength;
        if ($charratio > 2 / 3) {
            return [true, 'danger', $maxlength];
        }

        if ($charratio > 3 / 5) {
            return [true, 'warning', $maxlength];
        }

        return [false, 'warning', $maxlength];
    }

    /**
     * Render the translation editor element for users not using TinyMCE.
     *
     * The element is rendered standalone (outside of the QuickForm stack) so it can be injected in the mustache template.
     * Its name is the field key so that the JS can find it back (textarea[name="<KEY>[text]"] or its contenteditable).
     *
     * @param string $key Field key used as element name.
     * @param int $fieldformat Field text format.
     * @return string
     */
    protected function rendercteditor(string $key, int $fieldformat): string {
        $editor = new MoodleQuickForm_cteditor($key, '', ['id' => 'id_' . $key, 'rows' => 5]);
        $editor->setValue(['text' => '', 'format' => $fieldformat, 'itemid' => 0]);
        return $editor->toHtml();
    }
}
