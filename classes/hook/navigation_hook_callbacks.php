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

namespace local_deepler\hook;

use core\hook\navigation\secondary_extend;
use local_deepler\local\services\navigation_helper;

/**
 * Navigation hook callbacks for local_deepler.
 *
 * @package    local_deepler
 * @copyright  2026 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class navigation_hook_callbacks {
    /**
     * Extend secondary navigation for activities or sections.
     *
     * @param secondary_extend $hook
     * @return void
     */
    public static function extend_secondary_navigation(secondary_extend $hook): void {
        navigation_helper::extend_secondary_navigation($hook);
    }
}
