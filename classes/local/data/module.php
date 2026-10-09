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
use renderer_base;

/**
 * Class module wraps a cm_info object and provides a way to access its fields.
 *
 * @package local_deepler
 * @copyright 2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class module implements editable_interface, iconic_interface, translatable_interface, visibility_interface {
    /**
     * Constructor
     *
     * @param \cm_info $cminfo
     * @throws \coding_exception|\dml_exception
     */
    public function __construct(cm_info $cminfo) {
        $this->childs = [];
        $this->cm = $cminfo;
        $this->modname = $this->cm->modname;

        $this->pluginname = get_string('pluginname', $this->modname);

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
     * Fetch the childs of the module.
     *
     * @return void
     * @throws \dml_exception
     */
    private function fetchchilds(): void {
        $path = "local_deepler\local\data\subs\\{$this->modname}";
        $class = "\\$path";
        if ($this->modname === 'quiz') {
            $quiz = new $class($this->cm);
            $this->childs = $quiz->getchilds();
        } else {
            $item = field::createclassfromstring($this->modname, $this->cm);
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
     * Getter fo CM.
     *
     * @return \cm_info
     */
    public function get_cm(): cm_info {
        return $this->cm;
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
     * Check if the module has childs.
     *
     * @return bool
     */
    public function haschilds(): bool {
        return !empty($this->childs);
    }

    /**
     * Get the fields of the module.
     *
     * The module's own table is always handled by getfieldsfrominfo (which honours the
     * configured fields/clauses); getadditionals only covers the other configured tables.
     * Should both still produce the same table + column, the additionals version is kept.
     *
     * @return array
     */
    public function getfields(): array {
        $frominfo = field::getfieldsfrominfo($this->cm);
        $additionals = field::getadditionals($this->cm);

        // Index additionals by table.column so duplicates can be detected.
        $overridden = [];
        foreach ($additionals as $f) {
            $overridden[$f->get_table() . '.' . $f->get_tablefield()] = true;
        }

        // Keep only frominfo fields that are not redefined in additionals.
        $base = array_values(array_filter($frominfo, function ($f) use ($overridden) {
            return !isset($overridden[$f->get_table() . '.' . $f->get_tablefield()]);
        }));
        return array_merge($base, $additionals);
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
     * @param renderer_base|null $output
     * @return string
     */
    public function geticon(?renderer_base $output = null): string {
        global $OUTPUT;
        $output = $output ?? $OUTPUT;

        // Moodle >= 5.0 provides core_course\output\activity_icon.
        if (class_exists('\core_course\output\activity_icon')) {
            $activityicon = \core_course\output\activity_icon::from_cm_info($this->cm)
                ->set_extra_classes('smaller courseicon align-self-start me-2 mr-2');
            return $output->render($activityicon);
        }

        // Fallback for Moodle 4.5.
        $iconurl = $this->cm->get_icon_url();
        $iconclass = 'activityicon icon' . ($iconurl->get_param('filtericon') ? '' : ' nofilter');
        $isbranded = component_callback('mod_' . $this->modname, 'is_branded', [], false);
        $purpose = plugin_supports('mod', $this->modname, FEATURE_MOD_PURPOSE, MOD_PURPOSE_OTHER);

        $context = [
            'icon' => $iconurl->out(false),
            'iconclass' => $iconclass,
            'purpose' => $purpose,
            'branded' => $isbranded,
            'pluginname' => (string) $this->pluginname,
            'cmid' => $this->cm->id,
            'showtooltip' => false,
        ];

        return $output->render_from_template('core_courseformat/local/content/cm/cmicon', $context);
    }

    /**
     * Get the plugin name of the module.
     *
     * @return string
     */
    public function getpluginname(): string {
        return $this->pluginname;
    }
    /** @var \cm_info */
    private cm_info $cm;
    /** @var string */
    private string $modname;
    /** @var \moodle_url */
    private moodle_url $link;
    /** @var string|lang_string */
    private string|lang_string $pluginname;
    /** @var array */
    private array $childs;
}
