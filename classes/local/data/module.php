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

namespace local_deepler\local\data;

use cm_info;
use lang_string;
use local_deepler\local\data\interfaces\editable_interface;
use local_deepler\local\data\interfaces\iconic_interface;
use local_deepler\local\data\interfaces\translatable_interface;
use local_deepler\local\data\interfaces\visibility_interface;
use moodle_url;

/**
 * Class module wraps a cm_info object and provides a way to access its fields.
 *
 * @package local_deepler
 * @copyright 2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class module implements translatable_interface, editable_interface, iconic_interface, visibility_interface {
    /** @var \cm_info */
    private cm_info $cm;
    /** @var string */
    private string $modname;
    /** @var \moodle_url */
    private moodle_url $link;
    /** @var string */
    private string|moodle_url $iconurl;
    /** @var string|lang_string */
    private string|lang_string $pluginname;
    /** @var string */
    private string $purpose;
    /** @var array */
    private array $childs;

    /**
     * Constructor
     *
     * @param \cm_info $cminfo
     */
    public function __construct(cm_info $cminfo) {
        $this->childs = [];
        $this->cm = $cminfo;
        $this->modname = $this->cm->modname;
        $this->iconurl = $this->cm->get_icon_url();
        $this->pluginname = get_string('pluginname', $this->modname);
        $this->purpose = call_user_func($this->modname . '_supports', FEATURE_MOD_PURPOSE);
        $this->link = $this->buildlink();
        $this->fetchchilds();
    }

    /**
     * Build the link to edit the module
     *
     * @return \moodle_url
     */
    private function buildlink(): moodle_url {
        $tableparts = explode("_", $this->modname);
        $moduletype = $tableparts[0];
        if (count($tableparts) > 1) {
            $path = "/mod/{$moduletype}/edit.php";
            $params['cmid'] = $this->cm->id;
        } else {
            $path = "/course/modedit.php";
            $params = ['update' => $this->cm->id];
        }
        return new moodle_url($path, $params);
    }

    /**
     * This method is used to check if the module is visible.
     *
     * @return bool
     */
    public function isvisible(): bool {
        return $this->cm->visible == true;
    }

    /**
     * Get the main translatable fields of the module.
     *
     * @return array
     */
    public function getmainfields(): array {
        return field::getfieldsfrominfo($this->cm);
    }

    /**
     * Fetch the childs of the module.
     *
     * @return void
     */
    private function fetchchilds(): void {
        $path = "local_deepler\local\data\subs\\{$this->modname}";
        $class = "\\$path";
        if ($this->modname === 'quiz') {
            $quiz = new $class($this->cm);
            $this->childs = $quiz->getchilds();
        } else {
            global $DB;
            $record = $DB->get_record($this->modname, ['id' => $this->cm->instance]);
            $item = field::createclassfromstring($this->modname, $record);
            if ($item) {
                $this->childs = [$item];
            }
        }
    }

    /**
     * Get the childs of the module.
     *
     * @return array
     */
    public function getchilds(): array {
        return $this->childs;
    }

    /**
     * Check if the module has childs.
     *
     * @return bool
     */
    public function haschilds() {
        return !empty($this->childs);
    }

    /**
     * Get the fields of the module.
     *
     * @return array
     */
    public function getfields(): array {
        return $this->getmainfields();
    }

    /**
     * Get the link to edit the module.
     *
     * @return string
     */
    public function getlink(): string {
        return $this->link->out();
    }

    /**
     * Get the icon of the activity module.
     *
     * @return string
     */
    public function geticon(): string {
        return $this->iconurl->out();
    }

    /**
     * Get the purpose of the module. Mainly used for CSS classes to color the icon.
     *
     * @return string
     */
    public function getpurpose(): string {
        return $this->purpose;
    }

    /**
     * Get the plugin name of the module.
     *
     * @return string
     */
    public function getpluginname(): string {
        return $this->pluginname;
    }
}
