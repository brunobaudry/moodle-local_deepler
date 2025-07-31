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
 * Local Deepler plugin settings.
 *
 * @package    local_deepler
 * @copyright  2022 Kaleb Heitzman
 * @copyright  2024 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core\notification;
use local_deepler\lib\admin_setting_tokenmanager;

// Plugin version display.
require_once(__DIR__ . '/version.php');
if (!isset($hassiteconfig)) {
    $hassiteconfig = false;
}

if ($hassiteconfig) {
    global $ADMIN;

    // Main settings page.
    $settings = new admin_settingpage('local_deepler', get_string('pluginname', 'local_deepler'));

    // Key setting.
    $settings->add(new admin_setting_configtext(
            'local_deepler/apikey',
            get_string('apikeytitle', 'local_deepler'),
            get_string('apikeytitle_desc', 'local_deepler'),
            '',
            PARAM_RAW_TRIMMED,
            40
    ));
    if (get_config('local_deepler', 'apikey')) {
        // Token manager.
        $settings->add(new admin_setting_description(
                'local_deepler/tokenmanagerlink',
                get_string('tokenmanager', 'local_deepler'),
                html_writer::link(
                        new moodle_url('/local/deepler/tokenmanager.php'),
                        get_string('tokengototokenmanager', 'local_deepler'),
                        ['target' => '_self']
                )
        ));
        $settings->add(new admin_setting_description(
                'local_deepler/glossaryadminlink',
                get_string('glossary:manage:title', 'local_deepler'),
                html_writer::link(
                        new moodle_url('/local/deepler/glossarymanageradmin.php'),
                        get_string('glossary:manage:title', 'local_deepler'),
                        ['target' => '_self']
                )
        ));
    }
    // Allow non found token to fall back to the common API key (might be smart to use a free key for that).
    $settings->add(new admin_setting_configcheckbox(
            'local_deepler/allowfallbackkey',
            get_string('allowfallbackkey', 'local_deepler'),
            get_string('allowfallbackkey_desc', 'local_deepler'),
            true
    ));

    // Hide iframes setting.
    $settings->add(new admin_setting_configcheckbox(
            'local_deepler/hideiframesadmin',
            get_string('hideiframesadmin', 'local_deepler'),
            get_string('hideiframesadmin_desc', 'local_deepler'),
            false
    ));

    // Escape LaTeX setting.
    $settings->add(new admin_setting_configcheckbox(
            'local_deepler/latexescapeadmin',
            get_string('latexescapeadmin', 'local_deepler'),
            get_string('latexescapeadmin_desc', 'local_deepler'),
            true
    ));

    // Escape <pre> tags setting.
    $settings->add(new admin_setting_configcheckbox(
            'local_deepler/preescapeadmin',
            get_string('preescapeadmin', 'local_deepler'),
            get_string('preescapeadmin_desc', 'local_deepler'),
            true
    ));

    // Minimum scanned field size.
    $settings->add(new admin_setting_configtext(
            'local_deepler/scannedfieldsize',
            get_string('scannedfieldsize', 'local_deepler'),
            get_string('scannedfieldsize_desc', 'local_deepler'),
            254,
            PARAM_INT,
            4
    ));

    // Breadcrumb max length.
    $settings->add(new admin_setting_configtext(
            'local_deepler/breadcrumblength',
            get_string('breadcrumblength', 'local_deepler'),
            get_string('breadcrumblength_desc', 'local_deepler'),
            30,
            PARAM_INT,
            4
    ));
    // Cookie duration.
    $settings->add(new admin_setting_configtext(
            'local_deepler/cookieduration',
            get_string('cookieduration', 'local_deepler'),
            get_string('cookieduration_desc', 'local_deepler'),
            703,
            PARAM_INT,
            4
    ));

    $settings->add(new admin_setting_description(
            'local_deepler/pluginversion',
            get_string('pluginversion', 'local_deepler'),
            $plugin->release ?? 'version'
    ));
    // Add the settings page to the admin menu.
    $ADMIN->add('localplugins', $settings);
    if (get_config('local_deepler', 'apikey')) {
        $ADMIN->add('localplugins', new admin_externalpage(
                'local_deepler_tokenmanager',
                get_string('tokenmanager', 'local_deepler'),
                new moodle_url('/local/deepler/tokenmanager.php'),
                'moodle/site:config'
        ));
        $ADMIN->add('localplugins', new admin_externalpage(
                'local_deepler_glossaryadmin',
                get_string('glossary:manage:title', 'local_deepler'),
                new moodle_url('/local/deepler/glossarymanageradmin.php'),
                'moodle/site:config'
        ));
    }
}
