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

use advanced_testcase;
use cm_info;

/**
 * Unit tests for the field class with a mod_data instance and its additional config.
 *
 * @package    local_deepler
 * @category   test
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_deepler\local\data\field
 */
final class field_mod_data_test extends advanced_testcase {
    /** @var \cm_info */
    private cm_info $cm;

    /**
     * Test setup: a database activity with populated templates and the mod_data additional config.
     *
     * @return void
     * @throws \moodle_exception
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $data = $generator->create_module('data', [
            'course' => $course->id,
            'name' => 'Test Database',
            'intro' => '<p>Intro <b>html</b></p>',
            'introformat' => FORMAT_HTML,
        ]);
        $DB->update_record('data', (object) [
            'id' => $data->id,
            'singletemplate' => '<div>##name##</div>',
            'listtemplate' => '<td>##name##</td>',
            'listtemplateheader' => '<table class="list"><tr>',
            'listtemplatefooter' => '</tr></table>',
            'addtemplate' => '<div>[[name]]</div>',
            'rsstemplate' => '<p>##name##</p>',
            'rsstitletemplate' => '##name##',
            'jstemplate' => 'console.log(1)',
            'asearchtemplate' => '<div>[[name]]</div>',
        ]);
        $this->cm = get_fast_modinfo($course)->get_cm($data->cmid);

        field::$additionals = [
            'mod_data' => [
                'data' => [
                    'fields' => [
                        'name' => null,
                        'intro' => null,
                        'singletemplate' => ['editable' => false],
                        'listtemplate' => ['editable' => false],
                        'listtemplatefooter' => ['editable' => false],
                        'listtemplateheader' => ['editable' => false],
                        'addtemplate' => ['editable' => false],
                    ],
                ],
            ],
        ];
    }

    /**
     * Reset the static config.
     *
     * @return void
     */
    public function tearDown(): void {
        field::$additionals = [];
        field::$filteredtablefields = [];
        parent::tearDown();
    }

    /**
     * Index fields by column name.
     *
     * @param field[] $fields
     * @return field[]
     */
    private function bycolumn(array $fields): array {
        $indexed = [];
        foreach ($fields as $f) {
            $indexed[$f->get_tablefield()] = $f;
        }
        return $indexed;
    }

    /**
     * The configured module table is restrictive, keeps formats and editable clauses, without PHP warnings.
     *
     * @covers ::getfieldsfrominfo
     * @covers ::getfieldsfromcolumns
     * @return void
     */
    public function test_getfieldsfrominfo_honours_mod_data_config(): void {
        $fields = $this->bycolumn(field::getfieldsfrominfo($this->cm));

        $this->assertEqualsCanonicalizing(
            ['name', 'intro', 'singletemplate', 'listtemplate', 'listtemplatefooter', 'listtemplateheader', 'addtemplate'],
            array_keys($fields)
        );
        $this->assertArrayNotHasKey('jstemplate', $fields);
        $this->assertArrayNotHasKey('rsstemplate', $fields);
        $this->assertArrayNotHasKey('rsstitletemplate', $fields);
        $this->assertArrayNotHasKey('asearchtemplate', $fields);

        $this->assertSame(1, $fields['intro']->get_format());
        $this->assertTrue($fields['intro']->iseditable());
        $this->assertTrue($fields['name']->iseditable());
        foreach (['singletemplate', 'listtemplate', 'listtemplatefooter', 'listtemplateheader', 'addtemplate'] as $col) {
            $this->assertFalse($fields[$col]->iseditable(), "$col should not be editable");
        }
        $this->assertSame('<table class="list"><tr>', $fields['listtemplateheader']->get_text());
        $this->assertDebuggingNotCalled();
    }

    /**
     * The module's own table is not processed again by getadditionals.
     *
     * @covers ::getadditionals
     * @return void
     */
    public function test_getadditionals_skips_module_own_table(): void {
        $this->assertSame([], field::getadditionals($this->cm));
    }

    /**
     * The module fields are returned once, intro keeps its format.
     *
     * @covers \local_deepler\local\data\module::getfields
     * @return void
     */
    public function test_module_getfields(): void {
        $module = new module($this->cm);
        $fields = $module->getfields();
        $this->assertCount(7, $fields);
        $fields = $this->bycolumn($fields);
        $this->assertSame(1, $fields['intro']->get_format());
        $this->assertFalse($fields['listtemplatefooter']->iseditable());
    }

    /**
     * When no fields are configured for the module table, all text columns are used except the skipped ones.
     *
     * @covers ::getfieldsfrominfo
     * @return void
     */
    public function test_getfieldsfrominfo_without_config(): void {
        field::$additionals = ['mod_url' => []];
        $fields = $this->bycolumn(field::getfieldsfrominfo($this->cm));
        $this->assertArrayHasKey('name', $fields);
        $this->assertArrayHasKey('intro', $fields);
        $this->assertArrayHasKey('singletemplate', $fields);
        $this->assertArrayNotHasKey('jstemplate', $fields);
        $this->assertArrayNotHasKey('rsstemplate', $fields);
        $this->assertArrayNotHasKey('csstemplate', $fields);
        $this->assertDebuggingNotCalled();
    }

    /**
     * buildfieldsfromtableconfig selects the *format column when it exists.
     *
     * @covers ::buildfieldsfromtableconfig
     * @return void
     */
    public function test_buildfieldsfromtableconfig_is_format_aware(): void {
        $fields = $this->bycolumn(field::buildfieldsfromtableconfig(
            ['data' => ['id' => 'id', 'fields' => ['intro' => null, 'jstemplate' => ['editable' => false]]]],
            $this->cm->instance,
            $this->cm->id
        ));
        $this->assertSame(1, $fields['intro']->get_format());
        $this->assertFalse($fields['jstemplate']->iseditable());
    }

    /**
     * Exclude clauses in getfieldsfromcolumns: boolean true always skips, string skips on match, missing key is fine.
     *
     * @covers ::getfieldsfromcolumns
     * @return void
     */
    public function test_getfieldsfromcolumns_clauses(): void {
        $info = (object) ['id' => 1, 'a' => 'keep', 'b' => 'skip', 'c' => 'skipped', 'd' => 'x'];
        $columns = ['a' => ['editable' => false], 'b' => ['exclude' => 'skip'], 'c' => ['exclude' => true], 'd' => null];
        $fields = $this->bycolumn(field::getfieldsfromcolumns($info, 'testtable', $columns));
        $this->assertEqualsCanonicalizing(['a', 'd'], array_keys($fields));
        $this->assertFalse($fields['a']->iseditable());
        $this->assertTrue($fields['d']->iseditable());
        $this->assertDebuggingNotCalled();
    }
}
