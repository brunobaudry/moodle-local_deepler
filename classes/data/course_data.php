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

namespace local_deepler\data;

use core\context;

/**
 * Course Data Processor.
 *
 * Processess course data for moodleform. This class is logic heavy.
 *
 * @package    local_deepler
 * @copyright  2022 Kaleb Heitzman <kaleb@jamfire.io>
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * TODO MDL-000 It would be best if put in $colstoskip list in admin config.
 */
class course_data {
    /** @var string */
    protected $dbtable;
    /** @var \stdClass */
    protected $course;
    /** @var \course_modinfo|null */
    protected $modinfo;
    /** @var string */
    protected $lang;
    /** @var string */
    protected $contextid;
    /** @var \core\context */
    protected $context;
    /** @var string[]
     * List of db columns of type text that are know to be useless to tranlsate.
     */
    protected $colstoskip;

    /**
     * Class Construct.
     *
     * @param \stdClass $course
     * @param string $lang
     * @param context $context
     * @throws \moodle_exception
     */
    public function __construct(\stdClass $course, string $lang = null, context $context) {
        // Set db table.
        $this->dbtable = 'local_deepler';
        // Store context.
        $this->context = $context;
        // Set course.
        $this->course = $course;
        // Get the context.
        $this->contextid = $this->context->id;
        // Set modinfo.
        $modinfo = get_fast_modinfo($course);
        $this->modinfo = $modinfo;
        // Set language.
        $this->lang = $lang;
        // Set the db fileds to skipp.
        $this->colstoskip = ['displayoptions', 'parameters', 'outputformat', 'authors', 'changes', 'conditions',
                'reference', 'allowedqtypes', 'excluderoles', 'questions', 'csstemplate', 'config', 'firstpagetitle',
        ];
    }

    /**
     * Get Course Data via modinfo.
     *
     * @return array
     */
    public function getdata() {
        $coursedata = $this->getcoursedata();
        $sectiondata = $this->getsectiondata();
        $activitydata = $this->getactivitydata();
        // Sections added to the activity items.
        return $this->prepare_data($coursedata, $sectiondata, $activitydata);
    }

    /**
     * Prepare multidimentional array to re-arrange textfields to match course presentation.
     *
     * @param array $coursedata
     * @param array $sectiondata
     * @param array $activitydata
     * @return array[]
     */
    private function prepare_data(array $coursedata, array $sectiondata, array $activitydata) {
        $tab = ['0' => ['section' => $coursedata, 'activities' => []]];
        foreach ($sectiondata as $k => $v) {
            $tab[$v->id]['section'][] = $v;
        }
        foreach ($activitydata as $ak => $av) {
            // If the section is not found place it under the course data as general intro.
            $sectionid = isset($tab[$av->section]) ? $av->section : "0";
            $tab[$sectionid]['activities'][] = $av;
        }
        return $tab;
    }

    /**
     * Get Course Data.
     *
     * @return array
     */
    private function getcoursedata() {
        $coursedata = [];
        $course = $this->modinfo->get_course();
        $activity = new \stdClass();
        $activity->modname = 'course';
        $activity->id = null;
        $activity->section = null;
        if ($course->fullname) {
            $data = $this->build_data(
                    $course->id,
                    $course->fullname,
                    0,
                    'fullname',
                    $activity
            );
            array_push($coursedata, $data);
        }
        if ($course->shortname) {
            $data = $this->build_data(
                    $course->id,
                    $course->shortname,
                    0,
                    'shortname',
                    $activity
            );
            array_push($coursedata, $data);
        }
        if ($course->summary) {
            $data = $this->build_data(
                    $course->id,
                    $course->summary,
                    $course->summaryformat,
                    'summary',
                    $activity
            );
            array_push($coursedata, $data);
        }

        return $coursedata;
    }

    /**
     * Get Section Data.
     *
     * @return array
     */
    private function getsectiondata() {
        global $DB;
        $sections = $this->modinfo->get_section_info_all();
        $sectiondata = [];
        $activity = new \stdClass();
        $activity->modname = 'course_sections';
        $activity->id = null;
        $activity->section = null;
        foreach ($sections as $sk => $section) {
            $record = $DB->get_record('course_sections', ['course' => $this->course->id, 'section' => $sk]);
            if ($record->name) {
                $data = $this->build_data(
                        $record->id,
                        $record->name,
                        0,
                        'name',
                        $activity
                );
                array_push($sectiondata, $data);
            }
            if ($record->summary) {
                $data = $this->build_data(
                        $record->id,
                        $record->summary,
                        $record->summaryformat,
                        'summary',
                        $activity
                );
                array_push($sectiondata, $data);
            }
        }
        return $sectiondata;
    }

    /**
     * Looks at a db column and validates that it is of db type text.
     * And that it is not a know useless to try to translate.
     *
     * @param database_column_info $field
     * @return bool
     */
    private function filterdbfields($field) {
        return (($field->meta_type === "C" && $field->max_length > 254)
                        || $field->meta_type === "X")
                && !in_array($field->name, $this->colstoskip);
    }

    /**
     * Get Activity Data.
     *
     * @return array
     * TODO MDL-000 Parse recursive wiki pages. Though only for no collaborative wikis as built by students.
     */
    private function getactivitydata() {
        global $CFG;
        global $DB;
        $activitydata = [];

        foreach ($this->modinfo->instances as $instances) {
            foreach ($instances as $activity) {
                // Build first level activities.
                $activitydbrecord = $this->injectactivitydata($activitydata, $activity, $activity->modname);
                // Build outstanding subcontent.
                switch ($activity->modname) {
                    case 'book':
                        include_once($CFG->dirroot . '/mod/book/locallib.php');
                        $chapters = book_preload_chapters($activitydbrecord);
                        foreach ($chapters as $c) {
                            $this->injectbookchapter($activitydata, $c, $activity->section);
                        }
                        break;
                    case 'wiki':
                        include_once($CFG->dirroot . '/mod/wiki/locallib.php');
                        $wikis = wiki_get_subwikis($activitydbrecord->id);
                        foreach ($wikis as $wid => $wiki) {
                            $firstpage = wiki_get_first_page($wid);
                            $this->injectwikipage($activitydata, $firstpage, $activity->section);
                        }
                        break;
                }
            }
        }
        return $activitydata;
    }

    /**
     * Special function for book's subchapters.
     *
     * @param array $activities
     * @param mixed $chapter
     * @param int $section
     * @return void
     * @throws \dml_exception
     */
    private function injectbookchapter(array &$activities, mixed $chapter, int $section) {
        global $DB;
        $activity = new \stdClass();
        $activity->modname = 'book_chapters';
        $activity->section = $section;
        // Need to make sure the activity content is blank so that it is not replaced in the hacky get_file_url.
        $activity->content = '';
        $titledata = $this->build_data(
                $chapter->id,
                $chapter->title,
                0,
                'title',
                $activity
        );
        $contentdata = $this->build_data(
                $chapter->id,
                $chapter->content,
                1,
                'content',
                $activity
        );
        array_push($activities, $titledata);
        array_push($activities, $contentdata);
    }

    /**
     * Special functions for wiki pages.
     *
     * @param array $activities
     * @param mixed $chapter
     * @param int $section
     * @return void
     * @throws \dml_exception
     */
    private function injectwikipage(array &$activities, mixed $chapter, int $section) {
        global $DB;
        $activity = new \stdClass();
        $activity->modname = 'wiki_pages';
        $activity->section = $section;
        // Need to make sure the activity content is blank so that it is not replaced in the hacky get_file_url.
        $activity->content = '';
        $titledata = $this->build_data(
                $chapter->id,
                $chapter->title,
                0,
                'title',
                $activity
        );
        $contentdata = $this->build_data(
                $chapter->id,
                $chapter->cachedcontent,
                1,
                'cachedcontent',
                $activity
        );
        array_push($activities, $titledata);
        array_push($activities, $contentdata);
    }

    /**
     * Sub function that map the main activity generic types to our activitydata format.
     *
     * @param array $activities
     * @param mixed $activity
     * @param string $table
     * @return false|mixed|\stdClass
     * @throws \dml_exception
     */
    private function injectactivitydata(array &$activities, mixed $activity) {
        global $DB;
        $activitydbrecord = $DB->get_record($activity->modname, ['id' => $activity->instance]);
        // We build an array of all Text fields for this record.
        $columns = $DB->get_columns($activity->modname);
        // Just get db collumns we need (texts content).
        $textcols = array_filter($columns, [$this, 'filterdbfields']);
        $textcollumnskeys = array_keys($textcols);

        // Feed the data array with found text.
        foreach ($textcollumnskeys as $field) {
            if ($activitydbrecord->{$field} !== null && trim($activitydbrecord->{$field}) !== '') {
                $data = $this->build_data(
                        $activitydbrecord->id,
                        $activitydbrecord->{$field},
                        isset($activitydbrecord->{$field . 'format'}) ?? 0,
                        $field,
                        $activity
                );
                array_push($activities, $data);
            }
        }
        return $activitydbrecord;
    }

    /**
     * Build Data Item.
     *
     * @param int $id
     * @param string $text
     * @param int $format
     * @param string $field
     * @param mixed $activity
     * @return \stdClass
     * @throws \dml_exception
     */
    private function build_data(int $id, string $text, int $format, string $field, mixed $activity) {
        global $DB;
        // Activity stuff.
        $table = $activity->modname;
        $cmid = $activity->id;
        $sectionid = $activity->section;
        $status = $this->store_status_db($id, $table, $field);
        // Build item id, tid, displaytext, format, table, field, tneeded, section.
        $item = new \stdClass();
        // Object stuff.
        $item->id = $id;
        $item->tid = $status->id;
        $item->displaytext = $item->text = $text;
        // Additional text to display images.
        if (str_contains($text, '@@PLUGINFILE@@')) {
            if (isset($activity->content) && $activity->content != '') {
                // When activity->content is set the @@PLUGINFILE@@ inside are as URI.
                $item->displaytext = $activity->content;
            } else {
                try {
                    $item->displaytext = $this->get_file_url($text, $id, $table, $field, $cmid ?? 0);
                } catch (\moodle_exception $e) {
                    // Image not found leave the plugin file empty.
                    $item->displaytext = $item->text = $text;
                }
            }
        }
        $item->format = intval($format);
        $item->table = $table;
        $item->field = $field;
        $item->link = $this->link_builder($id, $table, $cmid);
        $item->tneeded = $status->s_lastmodified >= $status->t_lastmodified;
        $item->section = $sectionid;
        // Get the activity icon, if it is a real activity/resource.
        if ($cmid !== null) {
            $item->purpose = call_user_func($table . '_supports', FEATURE_MOD_PURPOSE);
            $item->iconurl = $this->modinfo->get_cm($cmid)->get_icon_url()->out(false);
        }

        return $item;
    }

    /**
     * Stores the translation's statuses.
     *
     * @param int $id
     * @param string $table
     * @param string $field
     * @return false|mixed|\stdClass
     * @throws \dml_exception
     */
    private function store_status_db(int $id, string $table, string $field) {
        global $DB;
        // Skip if target lang is undefined.
        if ($this->lang == null) {
            $dummy = new \stdClass();
            $dummy->id = "0";
            $dummy->s_lastmodified = "0";
            $dummy->t_lastmodified = "0";
            return $dummy;
        }
        // Build db params.
        $params = [
                't_id' => $id,
                't_lang' => $this->lang,
                't_table' => $table,
                't_field' => $field,
        ];

        // Insert tracking record if it does not exist.
        if (!$DB->record_exists($this->dbtable, $params)) {
            $time = time();
            $params['s_lastmodified'] = $time;
            $params['t_lastmodified'] = $time;
            $id = $DB->insert_record($this->dbtable, $params);
            $record = $DB->get_record($this->dbtable, ['id' => $id], 'id,s_lastmodified,t_lastmodified');
        } else {
            $record = $DB->get_record($this->dbtable, $params, 'id,s_lastmodified,t_lastmodified');
        }
        return $record;
    }

    /**
     * Link Builder.
     *
     * @param integer $id
     * @param string $table
     * @param integer $cmid
     * @return string
     */
    private function link_builder($id, $table, $cmid) {
        $link = null;
        $tcmid = $cmid ?? 0;
        switch ($table) {
            case 'course':
                $link = "/course/edit.php?id={$id}";
                break;
            case 'course_sections':
                $link = "/course/editsection.php?id={$id}";
                break;
            default:
                if ($cmid !== 0) {
                    $link = "/course/modedit.php?update={$tcmid}";
                }
                break;
        }

        return $link;
    }

    /**
     * Get the correct context.
     *
     * @param int $id
     * @param string $table
     * @param int $cmid
     * @return array
     */
    private function get_item_contextid($id, $table, $cmid = 0) {
        $i = 0;
        $iscomp = false;

        switch ($table) {
            case 'course':
                $i = \context_course::instance($id)->id;
                break;
            case 'course_sections':
                $i = \context_module::instance($id)->id;
                break;
            default :
                $i = \context_module::instance($cmid)->id;
                $iscomp = true;
                break;
        }
        return ['contextid' => $i, 'component' => $iscomp ? 'mod_' . $table : $table, 'itemid' => $iscomp ? $cmid : ''];
    }

    /**
     * Retrieve the urls of files.
     *
     * @param string $text
     * @param int $itemid
     * @param string $table
     * @param string $field
     * @param int $cmid
     * @return array|string|string[]
     * @throws \dml_exception
     */
    private function get_file_url(string $text, int $itemid, string $table, string $field, int $cmid) {
        global $DB;
        $tmp = $this->get_item_contextid($itemid, $table, $cmid);
        switch ($table) {
            case 'course_sections' :
                $select =
                        'component = "course" AND itemid =' . $itemid . ' AND filename != "." AND filearea = "section"';
                $params = [];
                break;
            default :
                $select =
                        'contextid = :contextid AND component = :component AND filename != "." AND ' .
                        $DB->sql_like('filearea', ':field');
                $params = ['contextid' => $tmp['contextid'], 'component' => $tmp['component'],
                        'field' => '%' . $DB->sql_like_escape($field) . '%'];
                break;
        }

        $result = $DB->get_recordset_select('files', $select, $params);
        if ($result->valid()) {
            $itemid = ($field == 'intro' || $field == 'summary') && $table != 'course_sections' ? '' : $result->current()->itemid;
            return file_rewrite_pluginfile_urls($text, 'pluginfile.php', $result->current()->contextid,
                    $result->current()->component, $result->current()->filearea, $itemid);
        } else {
            return file_rewrite_pluginfile_urls($text, 'pluginfile.php', $tmp['contextid'], $tmp['component'], $field,
                    $tmp['itemid']);
        }
    }
}
