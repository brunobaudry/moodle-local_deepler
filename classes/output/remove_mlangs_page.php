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

use filter_multilang2;
use local_deepler\local\data\course;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Translate Page Output.
 *
 * Provides output class for /local/mlangremover/remove_mlangs.php
 *
 * @package    local_mlangremover
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class remove_mlangs_page implements renderable, templatable {
    /**
     * The course in translation.
     *
     * @var object
     */
    private object $course;
    /**
     * The data of the course parsed from mod_info.
     *
     * @var array
     */
    private array $coursedata;

    /**
     * The form to display the row UI.
     *
     * @var remove_mlangs_form
     */
    private remove_mlangs_form $mform;
    /**
     * The release version.
     *
     * @var string
     */
    private $pluginversion;
    /** @var string[]
     * All the mlang tags used in the course.
     * */
    private array $mlangtags;
    private filter_multilang2 $mlangfilter;

    /**
     * Class Construct.
     *
     * @param \stdClass $course
     * @param array $coursedata
     * @throws \moodle_exception
     */
    public function __construct(course $course, filter_multilang2 $mlangfilter, string $version) {
        $this->mlangfilter = $mlangfilter;
        $this->pluginversion = $version;
        $this->course = $course;
        // Moodle Form.
        $mform = new remove_mlangs_form(null, ['coursedata' => $this->course, 'mlangfilter' => $mlangfilter]);
        $this->mform = $mform;
    }

    /**
     * Export Data to Template.
     *
     * @param renderer_base $output
     * @return object
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        // Data for mustache template.
        $data->course = $this->course;
        $renderedform = $this->mform->render();
        $data->mform = $renderedform;
        // Set langs.
        $data->mlangfilter = $this->mlangfilter;
        // $data->escapelatexbydefault = get_config('local_deepler', 'latexescapeadmin') ? 'checked' : '';
        // Pass data.
        $data->version = $this->pluginversion;
        // var_dump($data);
        return $data;
    }
}
