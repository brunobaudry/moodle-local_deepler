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

use core\output\renderer_base;
use renderable;
use stdClass;
use templatable;

/**
 * Translate Page Output.
 * Provides output class for /local/deepler/translate.php when error with connecting to the api is found.
 *
 * @package    local_deepler
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class badsettings_page implements renderable, templatable {

    /**
     * @var string
     */
    private string $onomatopoeia;
    /**
     * @var \core\output\renderer_base
     */
    private renderer_base $output;

    /**
     * Constructor.
     *
     * @param string $onomatopoeia
     */
    public function __construct(string $onomatopoeia) {
        $this->onomatopoeia = $onomatopoeia;
    }

    /**
     * Mandatory for renderer.
     *
     * @param \core\output\renderer_base $output
     * @return \stdClass
     */
    public function export_for_template(renderer_base $output) {
        $this->output = $output;
        $data = new stdClass();
        $data->onomatopoeia = $this->onomatopoeia;
        return $data;
    }
}
