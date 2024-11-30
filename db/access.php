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
 * Moodle Edit Translations Permissions
 *
 * Adds local/deepler:eddittranslations permissions for checking against
 * the webservice.
 *
 * @package    local_deepler
 * @copyright  2022 Kaleb Heitzman <kaleb@jamfire.io>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/Access_API
 */

defined('MOODLE_INTERNAL') || die();

// Translator Capabilities.
$capabilities = [
        'local/deepler:edittranslations' => [
                'captype' => 'write',
                'riskbitmask' => 'RISK_XSS',
                'contextlevel' => CONTEXT_COURSE,
                'archetypes' => ['manager' => CAP_ALLOW],
        ],
];
