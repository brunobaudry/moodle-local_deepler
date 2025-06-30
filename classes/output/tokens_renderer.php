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

use html_table;
use html_writer;
use local_deepler\local\services\utils;
use moodle_url;
use pix_icon;
use plugin_renderer_base;

/**
 * Admin page to manage tokens.
 *
 * @package local_deepler
 * @copyright 2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tokens_renderer extends plugin_renderer_base {

    /**
     * Renders the token manager UI: table of attribute-token mappings and add form.
     *
     * @return string HTML output
     */
    public function render_token_manager() {
        global $DB;
        global $SESSION;
        $html = '';

        if (!empty($SESSION->local_deepler_errors)) {
            foreach ($SESSION->local_deepler_errors as $error) {
                $html .= html_writer::div($error, 'alert alert-danger');
            }
            unset($SESSION->local_deepler_errors);
        }

        // Standard and custom fields for the dropdown.
        $userfields = utils::all_user_fields();

        // Fetch existing mappings from the DB.
        $records = $DB->get_records('local_deepler_tokens');

        // Build the HTML output.
        $html = '';

        // Table of existing mappings (attributes-tokens).
        $table = new html_table();
        $table->head = [
                get_string('tokenattribute', 'local_deepler'),
                get_string('tokenvaluefilter', 'local_deepler'),
                get_string('tokentoken', 'local_deepler'),
                get_string('tokenactions', 'local_deepler'),
        ];
        $table->data = [];

        foreach ($records as $record) {
            $deleteurl = new moodle_url('/admin/settings.php', [
                    'section' => 'local_deepler',
                    'deletetoken' => $record->id,
                    'sesskey' => sesskey(),
            ]);
            $table->data[] = [
                    isset($userfields[$record->attribute]) ? $userfields[$record->attribute] : s($record->attribute),
                    s($record->valuefilter),
                    s($record->token),
                    $this->action_icon($deleteurl, new pix_icon('t/delete', get_string('delete'))),
            ];
        }

        // Add a comment or description.
        $html .= html_writer::div(get_string('tokenadminpagedescription', 'local_deepler'), 'adminpage-description');

        $html .= html_writer::tag('h3', get_string('tokentokenmanager_mappings', 'local_deepler'));
        $html .= html_writer::table($table);

        // Add new mapping form (as a separate section below the table).
        $html .= html_writer::tag('h4', get_string('tokentokenmanager_addnew', 'local_deepler'));
        $html .= html_writer::start_tag('form', [
                'method' => 'post',
                'action' => new moodle_url('/local/deepler/tokenmanager.php'),
                'class' => 'mform',
        ]);
        $html .= html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => 'sesskey',
                'value' => sesskey(),
        ]);

        $html .= html_writer::start_div('form-inline');
        $html .= html_writer::select($userfields, 'attribute', '', ['' => get_string('choose')],
                ['class' => 'custom-select mr-2', 'id' => 'deepler-attribute']);
        $html .= html_writer::empty_tag('input', [
                'type' => 'text',
                'name' => 'valuefilter',
                'class' => 'form-control mr-2',
                'id' => 'deepler-valuefilter',
                'placeholder' => get_string('tokenfiltervalue', 'local_deepler'),
        ]);
        $html .= html_writer::empty_tag('input', [
                'type' => 'text',
                'name' => 'token',
                'class' => 'form-control mr-2',
                'id' => 'deepler-token',
                'placeholder' => get_string('tokentoken', 'local_deepler'),
                'size' => 40, // Or 36 for exact UUID length.
        ]);

        $html .= html_writer::empty_tag('input', [
                'type' => 'submit',
                'name' => 'addtoken',
                'value' => get_string('tokenadd', 'local_deepler'),
                'class' => 'btn btn-primary',
        ]);
        $html .= html_writer::end_div();

        $html .= html_writer::end_tag('form');
        $html .= html_writer::div(' ', 'pt-2', ['id' => 'deepler-form-errors']);
        $html .= html_writer::div(
                html_writer::link(
                        new moodle_url('/admin/settings.php', ['section' => 'local_deepler']),
                        get_string('tokengobacktosettings', 'local_deepler'),
                        ['target' => '_self']
                ),
                'mb-3 pt-4'
        );

        $this->page->requires->js_call_amd('local_deepler/formvalidation', 'init');
        return $html;
    }
}
