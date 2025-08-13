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

use core\output\html_writer;
use local_deepler\local\services\utils;
use moodle_url;
use plugin_renderer_base;
use local_deepler\local\services\spreadsheetglossaryparser;

/**
 * Sub renderer for Glossary stuff.
 *
 * @package local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class glossary_renderer extends plugin_renderer_base {
    /**
     * For user's preferences. Tables with delete/share actions.
     *
     * @param array $glossaries
     * @return string
     * @throws \coding_exception|\core\exception\moodle_exception
     */
    public function glossary_table(array $glossaries): string {
        $g = array_change_key_case($glossaries, CASE_LOWER);
        $data = [];

        foreach ($g as $glo) {
            $deleteurl = new moodle_url('/local/deepler/glossarydelete.php', [
                    'section' => 'local_deepler',
                    'deleteglossary' => $glo->id,
                    'glossaryname' => $glo->name,
                    'glossarytoken' => $glo->glossaryid,
                    'sesskey' => sesskey(),
                    'redirect' => 'user',
            ]);

            $data[] = [
                    'name' => format_string($glo->name),
                    'glossaryid' => format_string($glo->glossaryid),
                    'sourcelang' => format_string($glo->sourcelang),
                    'targetlang' => format_string($glo->targetlang),
                    'entrycount' => format_string($glo->entrycount),
                    'lastused' => $glo->lastused === 0 ? get_string('glossary:neverused', 'local_deepler') :
                            userdate($glo->lastused),
                    'entrycountlink' => $this->generateentrieslink($glo),
                    'shared' => $this->dovisibilityoptions($glo->shared, false),
                    'actions' => utils::local_deepler_get_action_icon($deleteurl, 't/delete', get_string('delete')),
            ];
        }

        return $this->render_from_template('local_deepler/glossary_table', ['glossaries' => $data]);
    }

    /**
     * Read only tables.
     *
     * @param array $glossaries
     * @param string $title
     * @return string
     * @throws \coding_exception
     * @throws \core\exception\moodle_exception
     */
    public function glossary_table_view(array $glossaries, string $title): string {
        $g = array_change_key_case($glossaries, CASE_LOWER);
        $data = [];

        foreach ($g as $glo) {

            $data[] = [
                    'name' => format_string($glo->name),
                    'glossaryid' => format_string($glo->glossaryid),
                    'sourcelang' => format_string($glo->sourcelang),
                    'targetlang' => format_string($glo->targetlang),
                    'entrycount' => format_string($glo->entrycount),
                    'lastused' => $glo->lastused === 0 ? get_string('glossary:neverused', 'local_deepler') :
                            userdate($glo->lastused),
                    'entrycountlink' => $this->generateentrieslink($glo),
            ];
        }

        return $this->render_from_template('local_deepler/glossary_table_view',
                [
                        'glossaries' => $data,
                        'title' => $title,
                ]);
    }

    /**
     * Admin glossary management renderer.
     *
     * @param array $glossaries
     * @return string
     */
    public function glossaries_table_admin(array $glossaries): string {
        $g = array_change_key_case($glossaries, CASE_LOWER);
        $data = [];

        foreach ($g as $glo) {
            $deleteurl = new moodle_url('/local/deepler/glossarydelete.php', [
                    'section' => 'local_deepler',
                    'deleteglossary' => $glo->id,
                    'glossaryname' => $glo->name,
                    'glossarytoken' => $glo->glossaryid,
                    'sesskey' => sesskey(),
                    'redirect' => 'admin',
            ]);
            $data[] = [
                    'name' => format_string($glo->name),
                    'glossaryid' => format_string($glo->glossaryid),
                    'sourcelang' => format_string($glo->sourcelang),
                    'targetlang' => format_string($glo->targetlang),
                    'entrycount' => format_string($glo->entrycount),
                    'entrycountlink' => $this->generateentrieslink($glo),
                    'shared' => $this->dovisibilityoptions($glo->shared),
                    'tokenid' => $glo->tokenid === 0 ? get_string('glossary:pool:admin', 'local_deepler') : $glo->tokenid,
                    'lastused' => $glo->lastused === 0 ? get_string('glossary:neverused', 'local_deepler') :
                            userdate($glo->lastused),
                    'actions' =>
                            utils::local_deepler_get_action_icon($deleteurl, 't/delete', get_string('delete')),

            ];
        }
        return $this->render_from_template('local_deepler/glossary_table_admin', [
                'glossaries' => $data,
                'settingsurl' => (new moodle_url('/admin/settings.php', ['section' => 'local_deepler']))->out(),
                'backtosettings' => get_string('tokengobacktosettings', 'local_deepler'),
                'tokensettingsurl' => (new moodle_url('/local/deepler/tokenmanager.php', ['section' => 'local_deepler']))->out(),
                'tokensettings' => get_string('tokensettings', 'local_deepler'),
        ]);
    }

    /**
     * View entries link renderer.
     *
     * @param object $glo
     * @return \core\output\action_icon|string
     * @throws \coding_exception
     */
    private function generateentrieslink(object $glo) {
        return utils::local_deepler_get_action_icon('#',
                'i/preview', get_string('view'),
                'core', [
                        'data-id' => 'local_deepler/glossary_entriesviewer',
                        'data-glossary' => $glo->glossaryid,
                        'data-source' => $glo->sourcelang,
                        'data-target' => $glo->targetlang,
                ]);
    }

    /**
     * Renderer for glossary upload.
     *
     * @param string $form
     * @return string
     * @throws \coding_exception
     * @throws \core\exception\moodle_exception
     */
    public function glossary_uploader(string $form): string {
        $data = [
                'redirect' => $form,
                'glossaryupload' => get_string('glossary:upload', 'local_deepler'),
                'formaction' => (new moodle_url('/local/deepler/glossaryupload.php'))->out(),
                'sesskey' => sesskey(),
                'glossaryexplcol1' => format_text(get_string('glossary:upload:file', 'local_deepler'), FORMAT_MARKDOWN,
                        ['trusted' => true]),
                'glossaryexplcol2' => format_text(get_string('glossary:upload:file:expla', 'local_deepler'), FORMAT_MARKDOWN,
                        ['trusted' => true]),
                'glossaryexplcol3' => format_text(get_string('glossary:upload:file:explb', 'local_deepler'), FORMAT_MARKDOWN,
                        ['trusted' => true]),
                'glossarydeepllink' => get_string('glossary:deepl:link', 'local_deepler'),
                'glossaryuploadbtn' => get_string('glossary:upload:btn', 'local_deepler'),
                'glossaryhelpmodaltitle' => get_string('glossary:helpmodal:title', 'local_deepler'),
                'close' => get_string('ok'),
                'fileaccept' => implode(',', spreadsheetglossaryparser::$supportedextensions),
        ];

        return $this->render_from_template('local_deepler/glossary_uploader', $data);
    }

    /**
     * Dropdown selection of available glossaries.
     *
     * @param array $glossaries
     * @param string $sourcelang
     * @param string $targetlang
     * @return string
     * @throws \coding_exception
     * @throws \core\exception\moodle_exception
     */
    public function glossay_selector_deepl(array $glossaries, string $sourcelang, string $targetlang): string {
        $glossarylist = [];
        foreach ($glossaries as $g) {
            if ($g->sourcelang === $sourcelang && $g->targetlang === $targetlang) {
                $nameadd = ' (' . get_string('glossary:visibility:private', 'local_deepler') . ')';
                if ($g->shared === 1) {
                    $nameadd = ' (' . get_string('glossary:visibility:pool', 'local_deepler') . ')';
                }
                if ($g->shared === 2) {
                    $nameadd = ' (' . get_string('glossary:visibility:public', 'local_deepler') . ')';
                }

                $glossarylist[] = [
                        'glossaryid' => $g->glossaryid,
                        'name' => $g->name . $nameadd,
                ];
            }
        }

        $data = [
                'glossarynotselected' => get_string('glossary:notselected', 'local_deepler'),
                'glossaryselectplaceholder' => get_string('glossary:selectplaceholder', 'local_deepler'),
                'glossarylistempty' => get_string('glossary:listempty', 'local_deepler'),
                'hasglossaries' => !empty($glossarylist),
                'glossaries' => $glossarylist,
                'linkentries' => utils::local_deepler_get_action_icon('#',
                        'e/find_replace', get_string('view'),
                        'core', ['data-id' => 'local_deepler/glossary_entriesviewer_page']),
        ];

        return $this->render_from_template('local_deepler/glossary_selector', $data);
    }

    /**
     * Sub function to generate a dropdown to select glossarie's visibility.
     *
     * @param int $current
     * @param bool $admin
     * @return string
     * @throws \coding_exception
     */
    private function dovisibilityoptions(int $current, bool $admin = true): string {
        $output = html_writer::tag('option',
                get_string('glossary:visibility:private', 'local_deepler'),
                ['value' => '0'] + ($current === 0 ? ['selected' => 'selected'] : [])
        );
        $output .= html_writer::tag('option',
                get_string('glossary:visibility:pool', 'local_deepler'),
                ['value' => '1'] + ($current === 1 ? ['selected' => 'selected'] : [])
        );
        if ($admin) {
            $output .= html_writer::tag('option',
                    get_string('glossary:visibility:public', 'local_deepler'),
                    ['value' => '2'] + ($current === 2 ? ['selected' => 'selected'] : [])
            );
        }
        return $output;
    }

    /**
     * Simple notifications' wrapper.
     *
     * @param string $type
     * @param string $status
     * @param string $data
     * @return string
     * @throws \coding_exception
     */
    public function handle_glossary_status($type, $status, $data): string {
        $key = $type . ':' . $status;

        if ($status !== 'success') {
            return $this->glossary_error(
                    get_string("glossary:{$key}:title", 'local_deepler'),
                    get_string("glossary:{$key}:body", 'local_deepler', $data)
            );
        } else {
            return $this->glossary_success(
                    get_string("glossary:{$key}:title", 'local_deepler'),
                    get_string("glossary:{$key}:body", 'local_deepler', $data)
            );
        }
    }

    /**
     * Render glossaries errors.
     *
     * @param string $title
     * @param string $message
     * @return string
     */
    public function glossary_error(string $title, string $message): string {
        return $this->notifications($title, $message, 'danger');
    }

    /**
     * Render glossaries success.
     *
     * @param string $title
     * @param string $message
     * @return string
     */
    public function glossary_success(string $title, string $message): string {
        return $this->notifications($title, $message, 'success');
    }

    /**
     * Renders glossaries warnings.
     *
     * @param string $title
     * @param string $message
     * @return null
     */
    public function glossary_warning(string $title, string $message) {
        return $this->notifications($title, $message, 'warning');
    }

    /**
     * Notification comon.
     *
     * @param string $title
     * @param string $message
     * @param string $type
     * @return string
     */
    private function notifications(string $title, string $message, string $type): string {
        $output = html_writer::start_div('alert alert-' . $type, ['role' => 'alert']);
        $output .= html_writer::tag('h5', $title);
        $output .= html_writer::tag('p', $message);
        $output .= html_writer::end_div();
        return $output;
    }

}
