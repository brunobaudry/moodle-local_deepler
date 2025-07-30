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
use core\output\pix_icon;
use core_table\output\html_table;
use DeepL\GlossaryInfo;
use moodle_url;
use plugin_renderer_base;

/**
 *
 *
 * @package local_deepler
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class glossary_renderer extends plugin_renderer_base {
    /**
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
                    'sesskey' => sesskey(),
            ]);

            $data[] = [
                    'name' => format_string($glo->name),
                    'glossaryid' => format_string($glo->glossaryid),
                    'sourcelang' => format_string($glo->sourcelang),
                    'targetlang' => format_string($glo->targetlang),
                    'entrycount' => format_string($glo->entrycount),
                    'actions' => $this->action_icon($deleteurl, new pix_icon('t/delete', get_string('delete'))),
            ];
        }

        return $this->render_from_template('local_deepler/glossary_table', ['glossaries' => $data]);
    }

    /**
     * @param array $glossaries
     * @return string
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
            ];
        }

        return $this->render_from_template('local_deepler/glossary_table_view',
                [
                        'glossaries' => $data,
                        'title' => $title,
                ]);
    }

    /**
     * @param array $glossaries
     * @return string
     */
    public function glossaries_table_admin(array $glossaries): string {
        $data = [
                'glossaries' => $glossaries
        ];
        return $this->render_from_template('local_deepler/glossary_table_admin', $data);
    }
    /**
     * Renderer for glossary upload.
     *
     * @return string
     * @throws \coding_exception|\core\exception\moodle_exception
     */
    public function glossary_uploader(): string {
        $data = [
                'glossaryupload' => get_string('glossaryupload', 'local_deepler'),
                'formaction' => (new moodle_url('/local/deepler/glossaryupload.php'))->out(),
                'sesskey' => sesskey(),
                'glossaryexplcol1' => format_text(get_string('glossaryuploadfile', 'local_deepler'), FORMAT_MARKDOWN,
                        ['trusted' => true]),
                'glossaryexplcol2' => format_text(get_string('glossaryuploadfileexpla', 'local_deepler'), FORMAT_MARKDOWN,
                        ['trusted' => true]),
                'glossaryexplcol3' => format_text(get_string('glossaryuploadfileexplb', 'local_deepler'), FORMAT_MARKDOWN,
                        ['trusted' => true]),
                'glossarydeepllink' => get_string('glossarydeepllink', 'local_deepler'),
                'glossaryuploadbtn' => get_string('glossaryuploadbtn', 'local_deepler'),
                'glossaryhelpmodaltitle' => get_string('glossaryhelpmodaltitle', 'local_deepler'),
                'close' => get_string('ok'),
        ];

        return $this->render_from_template('local_deepler/glossary_uploader', $data);
    }

    /**
     *
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
                $glossarylist[] = [
                        'glossaryid' => $g->glossaryid,
                        'name' => $g->name
                ];
            }
        }

        $data = [
                'glossaryselect' => get_string('glossaryselect', 'local_deepler'),
                'glossaryselectplaceholder' => get_string('glossaryselectplaceholder', 'local_deepler'),
                'glossarylistempty' => get_string('glossarylistempty', 'local_deepler'),
                'hasglossaries' => !empty($glossarylist),
                'glossaries' => $glossarylist
        ];

        return $this->render_from_template('local_deepler/glossary_selector', $data);
    }

    /**
     * @param string $title
     * @param string $message
     * @return string
     */
    public function glossary_error(string $title, string $message): string {

        $output = html_writer::start_div('alert alert-danger', ['role' => 'alert']);
        $output .= html_writer::tag('h5', $title);
        $output .= html_writer::tag('p', $message);
        $output .= html_writer::end_div();
        return $output;
    }

    /**
     * @param string $title
     * @param string $message
     * @return string
     */
    public function glossary_success(string $title, string $message): string {

        $output = html_writer::start_div('alert alert-success', ['role' => 'alert']);
        $output .= html_writer::tag('h5', $title);
        $output .= html_writer::tag('p', $message);
        $output .= html_writer::end_div();
        return $output;
    }
}
