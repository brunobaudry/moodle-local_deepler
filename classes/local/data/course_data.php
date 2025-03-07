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
use mod_hotquestion;
use mod_quiz\quiz_settings;
use question_definition;
use stdClass;

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
    protected $targetlangdeepl;
    /** @var string */
    protected $targetlangmoodle;
    /** @var string */
    protected $contextid;
    /** @var \context_course */
    protected $context;
    /** @var string[]
     * List of db columns of type text that are know to be useless to tranlsate for a specific mod.
     */
    protected $modcolstoskip;
    /** @var string[]
     * List of common db columns of type text that are know to be useless to tranlsate .
     */
    protected $comoncolstoskip;
    /** @var string[]
     * List of db columns of type text that the user decides they to be useless to tranlsate.
     */
    protected $usercolstoskip;
    /** @var int Do not scan DB field below that number. */
    protected $mintxtfieldsize;

    /**
     * Class Construct.
     *
     * @param \stdClass $course
     * @param string $lang
     * @param int $contextid
     * @throws \moodle_exception
     */
    public function __construct(stdClass $course, string $lang, int $contextid) {
        $this->mintxtfieldsize = get_config('local_deepler', 'scannedfieldsize');
        // Set db table.
        $this->dbtable = 'local_deepler';
        // Set course.
        $this->course = $course;
        // Get the context id.
        $this->contextid = $contextid;
        // Set modinfo.
        mlangable::$modinfo = get_fast_modinfo($course);
        //$this->modinfo = $modinfo;
        // $plugins = \core_component::get_plugin_types();
        // Set language.
        mlangable::$targetlangdeepl = $lang;
        $this->targetlangdeepl = $lang;
        $this->targetlangmoodle = strtolower(substr($lang, 0, 2));
        // Set the db fields to skipp.
        $this->comoncolstoskip = ['*_displayoptions', '*_stamp'];
        $this->modcolstoskip =
                ['url_parameters', 'hotpot_outputformat', 'hvp_authors', 'hvp_changes', 'lesson_conditions',
                        'scorm_reference', 'studentquiz_allowedqtypes', 'studentquiz_excluderoles', 'studentquiz_reportingemail',
                        'survey_questions', 'data_csstemplate', 'data_config', 'wiki_firstpagetitle',
                        'bigbluebuttonbn_moderatorpass', 'bigbluebuttonbn_participants', 'bigbluebuttonbn_guestpassword',
                        'rattingallocate_setting', 'rattingallocate_strategy', 'hvp_json_content', 'hvp_filtered', 'hvp_slug',
                        'wooclap_linkedwooclapeventslug', 'wooclap_wooclapeventid', 'kalvidres_metadata',
                ];
        $this->usercolstoskip = [];
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
            $tab[$v->id]['activities'] = []; // Initialise empty activities array.
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
        $course = mlangable::$modinfo->get_course();
        $activity = new activity('course', 0);
        if ($course->fullname) {
            $coursedata[] = new mlangable($course->id, $course->fullname, 0, 'fullname', $activity);
        }
        if ($course->shortname) {
            $coursedata[] = new mlangable($course->id, $course->fullname, 0, 'shortname', $activity);
        }
        if ($course->summary) {
            $coursedata[] = new mlangable($course->id, $course->fullname, 0, 'summary', $activity);
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
        $sections = mlangable::$modinfo->get_section_info_all();
        $sectiondata = [];
        $activity = new activity('course_sections', 0);
        foreach ($sections as $sk => $section) {
            $record = $DB->get_record('course_sections', ['course' => $this->course->id, 'section' => $sk]);
            if ($record->name) {
                $sectiondata[] = new mlangable($record->id, $record->name, 0, 'name', $activity);
            }
            if ($record->summary) {
                $sectiondata[] = new mlangable($record->id, $record->summary, $record->summaryformat, 'summary', $activity);
            }
        }
        return $sectiondata;
    }

    /**
     * Get Activity Data.
     *
     * @return array
     * TODO MDL-000 Parse recursive wiki pages. Though only for no collaborative wikis as built by students.
     */
    private function getactivitydata(): array {
        global $CFG;
        global $DB;
        $activitydata = [];
        // @todo MDL-000 wrap it as collections of Activity object
        $cms = mlangable::$modinfo->get_cms();
        foreach ($cms as $cmid => $coursemodule) {
            // Build first level activities.
            $activitydbrecord = $this->injectactivitydata($activitydata, $coursemodule, $cmid);
            // Build outstanding subcontent.
            switch ($coursemodule->modname) {
                case 'book':
                    include_once($CFG->dirroot . '/mod/book/locallib.php');
                    $chapters = book_preload_chapters($activitydbrecord);
                    foreach ($chapters as $c) {
                        $this->injectbookchapter($activitydata, $c, $coursemodule, $cmid);
                    }
                    break;
                case 'quiz':
                    // Get quiz questions.
                    $quizsettings = quiz_settings::create($coursemodule->instance);
                    $structure = \mod_quiz\structure::create_for_quiz($quizsettings);
                    $slots = $structure->get_slots();
                    foreach ($slots as $slot) {
                        try {
                            $question = \question_bank::load_question($slot->questionid);
                            $this->injectquizcontent($activitydata, $question, $coursemodule, $cmid);
                        } catch (\dml_read_exception $e) {
                            // Useless.
                            $activitydata[] = new mlangable(
                                    -1,
                                    $e->getMessage(), 0, '',
                                    new activity('quiz_questions', $question->id, $cmid, $coursemodule->sectionid, '', '',
                                            $slot->questionid),
                                    3
                            );
                        } catch (\moodle_exception $me) {
                            // Useless.
                            $activitydata[] = new mlangable(
                                    -1,
                                    $e->getMessage(), 0, '',
                                    new activity('quiz_questions', $question->id, $cmid, $coursemodule->sectionid, '', '',
                                            $slot->questionid),
                                    3
                            );
                        }
                    }
                    break;
                case 'wiki':
                    include_once($CFG->dirroot . '/mod/wiki/locallib.php');
                    $wikis = wiki_get_subwikis($activitydbrecord->id);

                    foreach ($wikis as $wid => $wiki) {
                        $pages = wiki_get_page_list($wid);
                        $pagesorphaned = array_map(function($op) {
                            return $op->id;
                        }, wiki_get_orphaned_pages($wid));
                        foreach ($pages as $p) {
                            if (!in_array($p->id, $pagesorphaned)) {
                                $this->injectwikipage($activitydata, $p, $coursemodule, $cmid);
                            }
                        }

                    }
                    break;
                case 'moodleoverflow':
                    include_once($CFG->dirroot . '/mod/moodleoverflow/locallib.php');
                    $discussion = moodleoverflow_get_discussions($coursemodule);
                    break;
                case 'hotquestion':
                    include_once($CFG->dirroot . '/mod/hotquestion/locallib.php');
                    $hq = new mod_hotquestion($coursemodule->id);
                    $questions = $hq->get_questions();
                    break;

            }
        }
        return $activitydata;
    }
    /**
     * Special function for book's subchapters.
     *
     * @param array $activities
     * @param mixed $chapter
     * @param \cm_info $act
     * @param int $cmid
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private function injectbookchapter(array &$activities, mixed $chapter, cm_info $act, int $cmid) {
        $activity = new activity('book_chapters', $act->id, $cmid, $act->get_section_info()->id);
        // Book chapters have title and content.
        $activities[] = new mlangable($chapter->id, $chapter->title, 0, 'chapter', $activity, 2, $cmid);
        $activities[] = new mlangable($chapter->id, $chapter->content, 1, 'content', $activity, 2, $cmid);
    }

    /**
     * Special function for wiki's subpages.
     *
     * @param array $activities
     * @param mixed $chapter
     * @param \cm_info $act
     * @param int $cmid
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @todo MDL-0 check differences between collaborative and individual
     */
    private function injectwikipage(array &$activities, mixed $chapter, cm_info $act, int $cmid) {
        global $DB;
        $activity = new activity('wiki_pages', $act->id, $cmid, $act->sectionid);
        // Wiki pages have title and cachedcontent.
        $activities[] = new mlangable($chapter->id, $chapter->title, 0, 'title', $activity, 2, $cmid);
        $activities[] = new mlangable($chapter->id, $chapter->cachedcontent, 1, 'cachedcontent', $activity, 2, $cmid);
    }



    /**
     * Sub function that map the main activity generic types to our activitydata format.
     *
     * @param array $activities
     * @param mixed $act
     * @param int $cmid
     * @return false|mixed|\stdClass
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private function injectactivitydata(array &$activities, mixed $act, int $cmid): mixed {
        $activity = new activity($act->modname, $act->id, $cmid, $act->get_section_info()->id);
        return $this->injectitem($activities, $act->modname, 'id', $act->instance, $activity);
    }

    /**
     * Special wrapper for questions subs to build data.
     *
     * @param array $activities
     * @param string $table
     * @param string $idcolname
     * @param int $id
     * @param mixed $act
     * @param int $cmid
     * @return false|mixed|\stdClass
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private function injectdatafromtable(array &$activities, string $table, string $idcolname, int $id, mixed $act,
            int $cmid, string $qtype = '') {
        global $DB;
        $activity = new activity($act->modname, $act->id, $cmid, $act->get_section_info()->id, '', $qtype);
        $activitydbrecord = $DB->get_record($table, [$idcolname => $id]);

        // Feed the data array with found text.
        foreach ($this->filterdbtextfields($table) as $field) {
            if ($activitydbrecord->{$field} !== null && trim($activitydbrecord->{$field}) !== '') {
                $activities[] = new mlangable(
                        $activitydbrecord->id,
                        $activitydbrecord->{$field},
                        isset($activitydbrecord->{$field . 'format'}) ?? 0,
                        $field,
                        $activity,
                        3, // @todo MDL-000 should refactor for dedup with injectactivitydata.
                        $cmid
                );
            }
        }
        return $activitydbrecord;
    }

    private function injectitem(array &$activities, string $table, string $idcolname, int $id, activity $activity) {
        global $DB;
        //$activity = new activity($act->modname, $act->id, $cmid, $act->get_section_info()->id,'', $qtype);
        $activitydbrecord = $DB->get_record($table, [$idcolname => $id]);

        // Feed the data array with found text.
        foreach ($this->filterdbtextfields($table) as $field) {
            if ($activitydbrecord->{$field} !== null && trim($activitydbrecord->{$field}) !== '') {
                $activities[] = new mlangable(
                        $activitydbrecord->id,
                        $activitydbrecord->{$field},
                        isset($activitydbrecord->{$field . 'format'}) ?? 0,
                        $field,
                        $activity,
                        3,
                        $activity->cmid
                );
            }
        }
        return $activitydbrecord;
    }

    /**
     * Question and Answers factory.
     *
     * @param array $activitydata
     * @param \question_definition $question
     * @param mixed $act
     * @param int $cmid
     * @return void
     * @throws \coding_exception
     * @throws \ddl_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private function injectquizcontent(array &$activitydata, question_definition $question, mixed $act, int $cmid) {
        global $DB;
        $dbmanager = $DB->get_manager(); // Get the database manager.

        $activity = new activity($act->modname, $act->id, $cmid, $act->get_section_info()->id);
        $activity->qtype = $question->qtype->plugin_name();
        $this->injectitem($activitydata, 'question', 'id', $question->id, $activity);
        // @todo MDL-000 pass $activity->qtype in child ?.
        $qactivity = new activity('question_answers', $act->id, $cmid, $act->sectionid, '', '', $activity);
        $qidfiledname = $question->qtype->questionid_column_name();

        $optionstablename = '';

        $hasoptions = false;
        if ($dbmanager->table_exists($activity->qtype . '_options')) {
            $optionstablename = $activity->qtype . '_options';
            $hasoptions = true;
        }
        if ($dbmanager->table_exists($activity->qtype . '_choice')) {
            $optionstablename = $activity->qtype . '_choice';
            $hasoptions = true;
        }
        switch ($activity->qtype) {
            case 'qtype_match':
                if ($dbmanager->table_exists($activity->qtype . '_subquestions')) {
                    $substablename = $activity->qtype . '_subquestions';
                    $qactivity->modname = $substablename;
                    if ($DB->record_exists($substablename, [$qidfiledname => $question->id])) {
                        $submatches = $DB->get_records($substablename, [$qidfiledname => $question->id]);
                        foreach ($submatches as $submatch) {
                            $this->injectitem(
                                    $activitydata,
                                    $substablename,
                                    'id',
                                    $submatch->id,
                                    $qactivity);
                        }

                    }
                }
                break;
            case 'qtype_multichoice':
                foreach ($question->answers as $answer) {
                    $activitydata[] = new mlangable($answer->id,
                            $answer->answer,
                            $answer->answerformat,
                            'answer',
                            $qactivity,
                            3,
                            $cmid);
                    if (!empty($answer->feedback)) {
                        $activitydata[] = new mlangable(
                                $answer->id,
                                $answer->feedback,
                                $answer->feedbackformat,
                                'feedback',
                                $qactivity,
                                3,
                                $cmid
                        );
                    }
                }
                break;
            case 'qtype_description':
            case 'qtype_randomsamatch':
            case 'qtype_essay':
            case 'qtype_multianswer':
                // Break as all the data is in the question table.
                break;
            case 'qtype_calculated':
            case 'qtype_calculatedsimple':
                foreach ($question->answers as $answer) {

                    if (!empty($answer->feedback)) {
                        $activitydata[] = new mlangable(
                                $answer->id,
                                $answer->feedback,
                                $answer->feedbackformat,
                                'feedback',
                                $qactivity,
                                3,
                                $cmid
                        );
                    }
                }
                break;
            case 'qtype_calculatedmulti':
                foreach ($question->answers as $answer) {
                    $activitydata[] = new mlangable(
                            $answer->id,
                            $answer->answer,
                            $answer->answerformat,
                            'answer',
                            $qactivity,
                            3,
                            $cmid
                    );
                    if (!empty($answer->feedback)) {
                        $activitydata[] = new mlangable(
                                $answer->id,
                                $answer->feedback,
                                $answer->feedbackformat,
                                'feedback',
                                $qactivity,
                                3,
                                $cmid
                        );
                    }
                }
                break;
            case 'qtype_truefalse':
                if (!empty($question->truefeedback)) {
                    $activitydata[] = new mlangable(
                            $question->trueanswerid,
                            $question->truefeedback,
                            $question->truefeedbackformat,
                            'feedback',
                            $qactivity,
                            3,
                            $cmid
                    );
                }
                if (!empty($question->falsefeedback)) {
                    $activitydata[] = new mlangable(
                            $question->falseanswerid,
                            $question->falsefeedback,
                            $question->falsefeedbackformat,
                            'feedback',
                            $qactivity,
                            3,
                            $cmid
                    );
                }
                break;
            case 'qtype_shortanswer':
                foreach ($question->answers as $answer) {
                    $activitydata[] = new mlangable(
                            $answer->id,
                            $answer->answer,
                            $answer->answerformat,
                            'answer',
                            $qactivity,
                            3,
                            $cmid
                    );
                    if (!empty($answer->feedback)) {
                        $activitydata[] = new mlangable(
                                $answer->id,
                                $answer->feedback,
                                $answer->feedbackformat,
                                'feedback',
                                $qactivity,
                                3,
                                $cmid
                        );
                    }
                }
            case 'qtype_numerical':
                foreach ($question->answers as $answer) {
                    if (!empty($answer->feedback)) {
                        $activitydata[] = new mlangable(
                                $answer->id,
                                $answer->feedback,
                                $answer->feedbackformat,
                                'feedback',
                                $qactivity,
                                3,
                                $cmid
                        );
                    }
                }
                break;
            case 'qtype_gapselect':
            case 'qtype_ddwtos' :
                $choices = $DB->get_records('question_answers', ['question' => $question->id]);
                foreach ($choices as $answer) {
                    $activitydata[] = new mlangable(
                            $answer->id,
                            $answer->answer,
                            0,
                            'answer',
                            $qactivity,
                            3,
                            $cmid
                    );
                }
                break;
            case 'qtype_ddimageortext' :
            case 'qtype_ddmarker' :
            $qactivity->modname = "{$activity->qtype}_drags";
                $choices = $DB->get_records($qactivity->modname, ['questionid' => $question->id]);
                foreach ($choices as $answer) {
                    if (trim($answer->label) === '') {
                        continue;
                    }
                    $activitydata[] = new mlangable(
                            $answer->id,
                            $answer->label,
                            0,
                            'label',
                            $qactivity,
                            3,
                            $cmid
                    );
                }
                break;
            case 'qtype_ordering' :
                foreach ($question->answers as $answer) {
                    $activitydata[] = new mlangable(
                            $answer->id,
                            $answer->answer,
                            $answer->answerformat,
                            'answer',
                            $qactivity,
                            3,
                            $cmid
                    );
                }
                break;
            default:
                // Log or handle unknown question types.
                debugging('Unhandled question type: ' . $question->qtype->name(), DEBUG_DEVELOPER);
                break;
        }
        if ($hasoptions && $DB->record_exists($optionstablename, [$qidfiledname => $question->id])) {
            $qactivity->modname = $optionstablename;
            $this->injectitem($activitydata, $optionstablename, $qidfiledname, $question->id, $qactivity);
        }
        if (count($question->hints)) {
            foreach ($question->hints as $hint) {
                $qactivity->modname = 'question_hints';
                $this->injectitem($activitydata, 'question_hints', 'id', $hint->id, $qactivity);
            }
        }
    }

    /**
     * Helper to list only interesting table fields.
     *
     * @param string $tablename
     * @return int[]|string[]
     */
    private function filterdbtextfields($tablename) {
        global $DB;
        // We build an array of all Text fields for this record.
        $columns = $DB->get_columns($tablename);

        // Just get db collumns we need (texts content).
        $textcols = array_filter($columns, function($field) use ($tablename) {
            // Only scan the main text types that are above minîmum text field size.
            return (($field->meta_type === "C" && $field->max_length > $this->mintxtfieldsize)
                            || $field->meta_type === "X")
                    && !in_array('*_' . $field->name, $this->comoncolstoskip)
                    && !in_array($tablename . '_' . $field->name, $this->usercolstoskip)
                    && !in_array($tablename . '_' . $field->name, $this->modcolstoskip);
        });
        return array_keys($textcols);
    }
}
