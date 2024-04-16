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
 * Local Course Translator Settings Page.
 *
 * @package    local_deepler
 * @copyright  2022 Kaleb Heitzman <kaleb@jamfire.io>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/Admin_settings
 */
defined('MOODLE_INTERNAL') || die();
if (has_capability('moodle/site:config', context_system::instance())) {
    global $ADMIN;
    // Create new settings page.
    $settings = new admin_settingpage('local_deepler', get_string('pluginname', 'local_deepler'));

    // Add to admin menu.
    $ADMIN->add('localplugins', $settings);

    // DeepL apikey.
    $settings->add(
            new admin_setting_configtext(
                    'local_deepler/apikey',
                    get_string('apikeytitle', 'local_deepler'),
                    get_string('apikeytitle_desc', 'local_deepler'),
                    null,
                    PARAM_RAW_TRIMMED,
                    40
            )
    );

    // DeepL Free or Pro?
    $settings->add(
            new admin_setting_configcheckbox(
                    'local_deepler/deeplpro',
                    get_string('deeplprotitle', 'local_deepler'),
                    get_string('deeplprotitle_desc', 'local_deepler'),
                    false
            )
    );
}
