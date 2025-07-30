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
     * @return string
     * @throws \coding_exception
     * @throws \core\exception\moodle_exception
     * @throws \dml_exception
     */
    public function render_token_manager(): string {
        global $DB, $SESSION;

        $data = [];

        // Errors.
        if (!empty($SESSION->local_deepler_errors)) {
            $data['errors'] = $SESSION->local_deepler_errors;
            unset($SESSION->local_deepler_errors);
        }

        // User fields.
        $userfields = utils::all_user_fields();

        // Records from DB.
        $records = $DB->get_records('local_deepler_tokens');
        $data['records'] = [];

        foreach ($records as $record) {
            $deleteurl = new moodle_url('/admin/settings.php', [
                    'section' => 'local_deepler',
                    'deletetoken' => $record->id,
                    'sesskey' => sesskey(),
            ]);

            $data['records'][] = [
                    'attribute' => $userfields[$record->attribute] ?? s($record->attribute),
                    'valuefilter' => s($record->valuefilter),
                    'token' => s($record->token),
                    'action' => $this->action_icon($deleteurl, new pix_icon('t/delete', get_string('delete')))
            ];
        }

        // Strings and form data.
        $data += [
                'description' => get_string('tokenadminpagedescription', 'local_deepler'),
                'mappingsheading' => get_string('tokentokenmanager_mappings', 'local_deepler'),
                'addnewheading' => get_string('tokentokenmanager_addnew', 'local_deepler'),
                'attribute' => get_string('tokenattribute', 'local_deepler'),
                'valuefilter' => get_string('tokenvaluefilter', 'local_deepler'),
                'token' => get_string('tokentoken', 'local_deepler'),
                'actions' => get_string('tokenactions', 'local_deepler'),
                'formaction' => (new moodle_url('/local/deepler/tokenmanager.php'))->out(),
                'sesskey' => sesskey(),
                'userattributeselect' => html_writer::select($userfields, 'attribute', '', ['' => get_string('choose')],
                        ['class' => 'custom-select mr-2', 'id' => 'deepler-attribute']),
                'valuefilterplaceholder' => get_string('tokenfiltervalue', 'local_deepler'),
                'tokenplaceholder' => get_string('tokentoken', 'local_deepler'),
                'addbutton' => get_string('tokenadd', 'local_deepler'),
                'settingsurl' => (new moodle_url('/admin/settings.php', ['section' => 'local_deepler']))->out(),
                'backtosettings' => get_string('tokengobacktosettings', 'local_deepler'),
        ];

        $this->page->requires->js_call_amd('local_deepler/formvalidation', 'init');

        return $this->render_from_template('local_deepler/token_manager', $data);
    }
}
