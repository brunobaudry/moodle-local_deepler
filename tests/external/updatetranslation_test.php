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

namespace local_deepler\external;
defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/local/deepler/tests/external/base_external.php');

use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use local_deepler\local\services\lang_helper;

/**
 * Test cases for update_translations external.
 *
 * @package    local_deepler
 * @copyright  2024 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see        https://docs.moodle.org/dev/PHPUnit
 */
final class updatetranslation_test extends base_external {
    /**
     * Data provider for testPrepareText.
     *
     * @return array
     */
    public static function dataprovider(): array {
        return [
            'Rephrasing with multilang' => [
                'data' => [
                    'table' => 'course',
                    'field' => 'summary',
                    'id' => 1,
                    'sourcecode' => lang_helper::REPHRASESYMBOL . 'en',
                    'mainsourcecode' => 'en',
                    'targetcode' => 'en',
                    'text' => 'new_text',
                    'sourcetext' => 'source_text',
                ],
                'fieldtext' => '{mlang other}source_text{mlang}',
                'expected' => [
                    'table' => 'course',
                    'field' => 'summary',
                    'id' => 1,
                    'sourcecode' => lang_helper::REPHRASESYMBOL . 'en',
                    'mainsourcecode' => 'en',
                    'targetcode' => 'en',
                    'text' => '{mlang other}new_text{mlang}',
                ],
            ],
            'Rephrasing with multilang but source not other' => [
                'data' => [
                    'table' => 'course',
                    'field' => 'summary',
                    'id' => 1,
                    'sourcecode' => lang_helper::REPHRASESYMBOL . 'en',
                    'mainsourcecode' => 'en',
                    'targetcode' => 'en',
                    'text' => 'new_text',
                    'sourcetext' => 'source_text',
                ],
                'fieldtext' => '{mlang en}source_text{mlang}',
                'expected' => [
                    'table' => 'course',
                    'field' => 'summary',
                    'id' => 1,
                    'sourcecode' => lang_helper::REPHRASESYMBOL . 'en',
                    'mainsourcecode' => 'en',
                    'targetcode' => 'en',
                    'text' => '{mlang en}new_text{mlang}',
                ],
            ],
            'Rephrasing with multilang but source differes from main' => [
                'data' => [
                    'table' => 'course',
                    'field' => 'summary',
                    'id' => 1,
                    'sourcecode' => lang_helper::REPHRASESYMBOL . 'fr',
                    'mainsourcecode' => 'en',
                    'targetcode' => 'fr',
                    'text' => 'nouveau_text',
                    'sourcetext' => 'source_text_fr',
                ],
                'fieldtext' => '{mlang fr}source_text_fr{mlang}',
                'expected' => [
                    'table' => 'course',
                    'field' => 'summary',
                    'id' => 1,
                    'sourcecode' => lang_helper::REPHRASESYMBOL . 'en',
                    'mainsourcecode' => 'en',
                    'targetcode' => 'en',
                    'text' => '{mlang fr}nouveau_text{mlang}',
                ],
            ],
            'Rephrasing without multilang' => [
                'data' => [
                    'table' => 'course',
                    'field' => 'summary',
                    'id' => 1,
                    'sourcecode' => lang_helper::REPHRASESYMBOL . 'en',
                    'mainsourcecode' => 'en',
                    'targetcode' => 'en',
                    'text' => 'new_text',
                    'sourcetext' => 'source_text',
                ],
                'fieldtext' => 'source_text',
                'expected' => [
                    'table' => 'course',
                    'field' => 'summary',
                    'id' => 1,
                    'sourcecode' => lang_helper::REPHRASESYMBOL . 'en',
                    'mainsourcecode' => 'en',
                    'targetcode' => 'en',
                    'text' => 'new_text',
                ],
            ],
            'Translation with multilang' => [
                'data' => [
                    'table' => 'course',
                    'field' => 'summary',
                    'id' => 1,
                    'sourcecode' => 'en',
                    'mainsourcecode' => 'en',
                    'targetcode' => 'de',
                    'text' => 'Hallo welt',
                    'sourcetext' => 'Hello world',
                ],
                'fieldtext' => '{mlang en}Hello world{mlang}',
                'expected' => [
                    'table' => 'course',
                    'field' => 'summary',
                    'id' => 1,
                    'sourcecode' => 'en',
                    'mainsourcecode' => 'en',
                    'targetcode' => 'de',
                    'text' => '{mlang en}Hello world{mlang}{mlang de}Hallo welt{mlang}',
                ],
            ],
            'Translation without multilang' => [
                'data' => [
                    'table' => 'course',
                    'field' => 'summary',
                    'id' => 1,
                    'sourcecode' => 'en',
                    'mainsourcecode' => 'fr',
                    'targetcode' => 'de',
                    'text' => 'Hallo welt',
                    'sourcetext' => 'Hello world',
                ],
                'fieldtext' => 'Hello world',
                'expected' => [
                    'table' => 'course',
                    'field' => 'summary',
                    'id' => 1,
                    'sourcecode' => 'en',
                    'mainsourcecode' => 'en',
                    'targetcode' => 'de',
                    'text' => '{mlang other}Hello world{mlang}{mlang de}Hallo welt{mlang}',
                ],
            ],
        ];
    }

    /**
     * Tests execute parameters.
     *
     * @covers \local_deepler\external\update_translation::execute_parameters
     * @return void
     */
    public function test_execute_parameters(): void {
        if ($this->is_below_four_one()) {
            return;
        }
        $params = update_translation::execute_parameters();
        $this->assertInstanceOf(external_function_parameters::class, $params);
    }

    /**
     * Tests execute returns.
     *
     * @covers \local_deepler\external\update_translation::execute_returns
     * @return void
     */
    public function test_execute_returns(): void {
        if ($this->is_below_four_one()) {
            return;
        }
        $returns = update_translation::execute_returns();
        $this->assertInstanceOf(external_multiple_structure::class, $returns);
    }

    /**
     * Tests update_translation success.
     *
     * @covers \local_deepler\external\update_translation::execute
     * @return void
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     */
    public function test_execute_success(): void {
        global $DB;
        if ($this->is_below_four_one()) {
            return;
        }
        $this->resetAfterTest();
        [$course, $user] = $this->create_test_course_and_user();
        $courseoldname = $course->fullname;
        $this->grant_capability($user, $course);
        $cid = $course->id;
        $deeplerrecord = (object) [
            't_id' => $cid,
            't_lang' => 'en',
            't_table' => 'course',
            't_field' => 'fullname',
            's_lastmodified' => time(),
            't_lastmodified' => time() - 3600, // 1 hour ago.
        ];
        $deeplerid = $DB->insert_record('local_deepler', $deeplerrecord);

        $data = [[
            'tid' => $deeplerid,
            'text' => $courseoldname . " transalted",
            'keyid' => "course[$cid][fullname][0]",
            'sourcecode' => 'en',
            'sourcetext' => $courseoldname,
            'targetcode' => 'de',
            'mainsourcecode' => 'other',

        ]];

        $result = update_translation::execute($data, $user->id, $course->id, 'update');

        // Prepare the data for the test.
        update_translation::preparedata($data[0]);
        update_translation::preparetext($data[0], $courseoldname);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('t_lastmodified', $result[0]);
        $this->assertArrayHasKey('text', $result[0]);
        $this->assertArrayHasKey('keyid', $result[0]);
        $this->assertEquals('course[' . $course->id . '][fullname][0]', $result[0]['keyid']);
        $this->assertEquals($data[0]['text'], $result[0]['text']);

        $updatedcourse = $DB->get_record('course', ['id' => $course->id]);
        $this->assertEquals($data[0]['text'], $updatedcourse->fullname);

        $updateddeepler = $DB->get_record('local_deepler', ['id' => $deeplerid]);
        $this->assertEquals($result[0]['t_lastmodified'], $updateddeepler->t_lastmodified);
    }

    /**
     * Tests the preparetext method that manipulates the texts and their main langs.
     *
     * @dataProvider dataprovider
     * @covers       \local_deepler\external\update_translation::preparetext
     * @param array $data
     * @param string $fieldtext
     * @param array $expected
     * @return void
     * @throws \coding_exception
     */
    public function test_preparetext(array $data, string $fieldtext, array $expected): void {
        if ($this->is_below_four_one()) {
            return;
        }
        $this->resetAfterTest();
        update_translation::preparetext($data, $fieldtext);
        $this->assertEquals($expected['text'], $data['text']);
    }
}
