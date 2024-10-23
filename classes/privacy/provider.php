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
 * Privacy Subsystem implementation for local_deepler.
 *
 * @package    filter_multilang2
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>,
 *             2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_deepler\privacy;

use core_privacy\local\metadata\null_provider;

class provider implements null_provider {
    /**
     * Privacy Subsystem implementation for local_deepler.
     *
     * @package    filter_multilang2
     * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>,
     *             2024 Bruno Baudry <bruno.baudry@bfh.ch>
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
