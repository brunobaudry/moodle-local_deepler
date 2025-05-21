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
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/Admin_settings
 */

defined('MOODLE_INTERNAL') || die();

if (has_capability('moodle/site:config', context_system::instance())) {
    global $ADMIN;
    // Create new settings page.
    $settings = new admin_settingpage('local_deepler', get_string('pluginname', 'local_deepler'));

    // DeepL apikey.
    $settings->add(
            new admin_setting_configtext(
                    'local_deepler/apikey',
                    get_string('apikeytitle', 'local_deepler'),
                    get_string('apikeytitle_desc', 'local_deepler'),
                    '',
                    PARAM_RAW_TRIMMED,
                    40
            )
    );
    // Do set if escaping hidding iframes is default.
    $settings->add(
            new admin_setting_configcheckbox(
                    'local_deepler/hideiframesadmin',
                    get_string('hideiframesadmin', 'local_deepler'),
                    get_string('hideiframesadmin_desc', 'local_deepler'),
                    false
            )
    );
    // Do set if escaping LaTeX tag is default.
    $settings->add(
            new admin_setting_configcheckbox(
                    'local_deepler/latexescapeadmin',
                    get_string('latexescapeadmin', 'local_deepler'),
                    get_string('latexescapeadmin_desc', 'local_deepler'),
                    true
            )
    );
    // Do set if escaping PRE tag is default.
    $settings->add(
            new admin_setting_configcheckbox(
                    'local_deepler/preescapeadmin',
                    get_string('preescapeadmin', 'local_deepler'),
                    get_string('preescapeadmin_desc', 'local_deepler'),
                    1
            )
    );
    // Min size of scanned fields.
    $settings->add(
            new admin_setting_configtext(
                    'local_deepler/scannedfieldsize',
                    get_string('scannedfieldsize', 'local_deepler'),
                    get_string('scannedfieldsize_desc', 'local_deepler'),
                    254,
                    PARAM_INT,
                    4
            )
    );
    // Plugin's version.
    require_once(__DIR__ . '/version.php');
    $settings->add(
            new admin_setting_description(
                    'local_deepler/pluginversion',
                    get_string('pluginversion', 'local_deepler'),
                    $plugin->release ?? 'version'
            )
    );
    // Add to admin menu.
    $ADMIN->add('localplugins', $settings);
}
