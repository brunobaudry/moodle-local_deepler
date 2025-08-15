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

use core_filters\text_filter;
use local_deepler\local\data\field;
use local_deepler\local\data\multilanger;
use local_deepler\local\services\lang_helper;
use renderer_base;

/**
 * Sub renderer for translate page stuff.
 *
 * @package local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class translate_renderer extends renderer_base {
    /**
     * Render field row.
     *
     * @param \local_deepler\local\data\field $field
     * @param \local_deepler\local\services\lang_helper $languagepack
     * @param \core_filters\text_filter|\local_deepler\output\Multilang2TextFilter $mlangfilter
     * @param string $editor
     * @return bool|string
     * @throws \coding_exception
     * @throws \core\exception\moodle_exception
     */
    public function makefieldrow(field $field,
            lang_helper $languagepack,
            text_filter|Multilang2TextFilter $mlangfilter,
            string $editor): bool|string {
        $key = $field->getkey();
        $keyid = $field->getkeyid();
        $cssclass = '';
        $tneeded = $field->get_status()->istranslationneeded();
        $status = $tneeded ? 'needsupdate' : 'updated';
        $iseditable = $field->iseditable();
        if (!$iseditable) {
            $cssclass = $cssclass . 'bg-light border-bottom border-secondary rounded-bottom mt-2';
        }
        // Hacky Special cases where the content is a db key (should never be translated).
        $isdbkey = str_contains($field->get_table(), 'wiki_pages') && $field->get_tablefield() === 'title';
        $buttonclass = '';
        $titlestring = '';
        $canrephrase = $languagepack->get_canimprove();
        $sametargetassource = $languagepack->isrephrase();
        $targetlang = $languagepack->targetlang;
        $currentlang = $languagepack->currentlang;
        if ((!$canrephrase && $sametargetassource) || $targetlang === '') {
            $buttonclass = 'badge-dark';
            $titlestring =
                    get_string($canrephrase ? 'doselecttarget' : 'canttranslate', 'local_deepler',
                            $targetlang);
        } else if ($tneeded) {
            if (str_contains($field->get_text(), "{mlang " . $targetlang)) {
                $buttonclass = 'badge-warning';
                $titlestring = get_string('needsupdate', 'local_deepler');
            } else {
                $buttonclass = $canrephrase && $sametargetassource ? 'badge-primary' : 'badge-danger';
                $titlestring = get_string($canrephrase && $sametargetassource ? 'neverrephrased' : 'nevertranslated',
                        'local_deepler',
                        $targetlang);
            }

        } else {
            $buttonclass = 'badge-success';
            $titlestring = get_string('uptodate', 'local_deepler');
        }
        // col 2
        $multilanger = new multilanger($field->get_text());
        $alllancodes = $multilanger->findmlangcodes();
        $langcodes = [];
        foreach ($alllancodes as $code) {
            if (!in_array($code, $langcodes)) {
                $langcodes[] = $code;
            }
        }
        $hasotherandsourcetag = $multilanger->has_multilandcode_and_others($currentlang);
        $alreadyhasmultilang = $multilanger->has_multilangs();
        $multilangdisabled = $alreadyhasmultilang ? '' : 'disabled';
        if ($alreadyhasmultilang) {
            if ($hasotherandsourcetag) {
                $badgeclass = 'danger';
                $multilangtitlestring = get_string('warningsource', 'local_deepler',
                        strtoupper($currentlang));
            } else {
                $multilangtitlestring = get_string('viewsource', 'local_deepler');
                $badgeclass = 'info';
            }
            $multilangtitlestring .= ' (' . implode(', ', $alllancodes) . ')';
        } else {
            $multilangtitlestring = get_string('viewsourcedisabled', 'local_deepler');
            $badgeclass = 'secondary';
        }
        $fieldtext = $field->get_text();
        $trimedtext = trim($fieldtext);
        $fieldformat = $field->get_format();
        $data = [
                'rowtitle' => $isdbkey ? get_string('translationdisabled', 'local_deepler') : '',
                'cssclass' => $cssclass,
                'flagandkey' => "$isdbkey$key",
                'status' => $iseditable ? $status : 'local_deepler/disabled',
                'fieldtranslation' => multilanger::findfieldstring($field),
                'titlestring' => htmlentities($titlestring, ENT_HTML5),
                'buttonclass' => $buttonclass,
                'key' => $key,
                'multilangtitlestring' => $multilangtitlestring,
                'keyid' => $keyid,
                'iseditable' => $iseditable,
                'badgeclass' => $badgeclass,
                'multilangdisabled' => $multilangdisabled,
                'selecttitle' => get_string('specialsourcetext', 'local_deepler',
                        strtoupper($currentlang)),
                'sourceoptions' => $languagepack->preparehtmlsources(),
                'rawsourcetext' => base64_encode($mlangfilter->filter($fieldtext) ?? ''),
                'rawunfilterdtext' => base64_encode($trimedtext),
                'mlangfiltered' => $mlangfilter->filter($field->get_displaytext()),
                'trimedtext' => $trimedtext,
                'table' => $field->get_table(),
                'cmid' => $field->get_cmid(),
                'id' => $field->get_id(),
                'tablefield' => $field->get_tablefield(),
                'tid' => $field->get_tid(),
                'fieldformat' => $fieldformat,
                'plaintextinput' => $fieldformat === 0,
                'istiny' => $this->editor === 'tiny',
        ];
        return $this->render_from_template('local_deepler/translate_field', $data);
    }
}
