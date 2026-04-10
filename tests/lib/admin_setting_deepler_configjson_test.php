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

namespace local_deepler\lib;

use advanced_testcase;

/**
 * Unit tests for admin_setting_deepler_configjson.
 *
 * @package    local_deepler
 * @category   test
 * @copyright  2026 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_deepler\lib\admin_setting_deepler_configjson
 */
final class admin_setting_deepler_configjson_test extends advanced_testcase {
    /** @var admin_setting_deepler_configjson */
    private admin_setting_deepler_configjson $setting;

    /**
     * Set up before each test.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        global $CFG;
        require_once($CFG->dirroot . '/lib/adminlib.php');
        $this->setting = new admin_setting_deepler_configjson(
            'local_deepler/additionalconf',
            'Additional conf',
            'desc',
            '',
            PARAM_RAW,
            '80',
            '30'
        );
    }

    /**
     * Empty string is always valid (triggers file fallback).
     *
     * @covers \local_deepler\lib\admin_setting_deepler_configjson::validate
     * @return void
     */
    public function test_validate_empty_string_is_valid(): void {
        $this->assertTrue($this->setting->validate(''));
    }

    /**
     * Whitespace-only string is treated as empty and is valid.
     *
     * @covers \local_deepler\lib\admin_setting_deepler_configjson::validate
     * @return void
     */
    public function test_validate_whitespace_only_is_valid(): void {
        $this->assertTrue($this->setting->validate("   \n  "));
    }

    /**
     * Valid JSON with simple key-value pairs passes validation.
     *
     * @covers \local_deepler\lib\admin_setting_deepler_configjson::validate
     * @return void
     */
    public function test_validate_simple_valid_json(): void {
        $json = json_encode(['key' => 'value', 'another_key' => 'another_value']);
        $this->assertTrue($this->setting->validate($json));
    }

    /**
     * Valid nested JSON matching the additional-conf schema passes validation.
     *
     * @covers \local_deepler\lib\admin_setting_deepler_configjson::validate
     * @return void
     */
    public function test_validate_nested_valid_json(): void {
        $config = [
            'qtype_essay' => [
                'qtype_essay_options' => [
                    'fields' => ['graderinfo' => null, 'responsetemplate' => null],
                ],
            ],
            'mod_url' => [
                'url' => [
                    'fields' => ['name' => null, 'intro' => null],
                ],
            ],
        ];
        $this->assertTrue($this->setting->validate(json_encode($config)));
    }

    /**
     * The bundled additional_conf.json file itself must pass validation.
     *
     * @covers \local_deepler\lib\admin_setting_deepler_configjson::validate
     * @return void
     */
    public function test_validate_bundled_json_file_is_valid(): void {
        $jsonfile = __DIR__ . '/../../additional_conf.json';
        $this->assertFileExists($jsonfile);
        $content = file_get_contents($jsonfile);
        $this->assertTrue($this->setting->validate($content));
    }

    /**
     * Malformed JSON returns an error string containing the parse-error lang string.
     *
     * @covers \local_deepler\lib\admin_setting_deepler_configjson::validate
     * @return void
     */
    public function test_validate_invalid_json_syntax_returns_error(): void {
        $brokenjson = '{"key": "value", "broken":}';
        $result = $this->setting->validate($brokenjson);
        $this->assertNotTrue($result);
        $this->assertIsString($result);
        $this->assertStringContainsString(get_string('additionalconf_parseerror', 'local_deepler'), $result);
    }

    /**
     * Top-level value that is not an object returns a schema error.
     *
     * @covers \local_deepler\lib\admin_setting_deepler_configjson::validate
     * @return void
     */
    public function test_validate_schema_root_must_be_object(): void {
        $result = $this->setting->validate(json_encode(['item1', 'item2']));
        $this->assertNotTrue($result);
        $this->assertIsString($result);
        $this->assertStringContainsString(get_string('additionalconf_schema_root', 'local_deepler'), $result);
    }

    /**
     * A plugin entry that is not an object (array of tables) returns a schema error.
     *
     * @covers \local_deepler\lib\admin_setting_deepler_configjson::validate
     * @return void
     */
    public function test_validate_schema_plugin_must_be_object(): void {
        $result = $this->setting->validate(json_encode(['qtype_foo' => 'not_an_object']));
        $this->assertNotTrue($result);
        $this->assertIsString($result);
        $this->assertStringContainsString(
            get_string('additionalconf_schema_plugin', 'local_deepler', 'qtype_foo'),
            $result
        );
    }

    /**
     * A table definition that is not an object returns a schema error.
     *
     * @covers \local_deepler\lib\admin_setting_deepler_configjson::validate
     * @return void
     */
    public function test_validate_schema_table_must_be_object(): void {
        $result = $this->setting->validate(json_encode(['qtype_foo' => ['some_table' => 'not_an_object']]));
        $this->assertNotTrue($result);
        $this->assertIsString($result);
        $this->assertStringContainsString(
            get_string('additionalconf_schema_table', 'local_deepler', 'some_table'),
            $result
        );
    }

    /**
     * A "fields" value that is not an object returns a schema error.
     *
     * @covers \local_deepler\lib\admin_setting_deepler_configjson::validate
     * @return void
     */
    public function test_validate_schema_fields_must_be_object(): void {
        $config = ['qtype_foo' => ['some_table' => ['fields' => 'not_an_object']]];
        $result = $this->setting->validate(json_encode($config));
        $this->assertNotTrue($result);
        $this->assertIsString($result);
        $this->assertStringContainsString(
            get_string('additionalconf_schema_fields', 'local_deepler', 'some_table'),
            $result
        );
    }

    /**
     * Config stored via set_config is retrievable and byte-for-byte identical.
     *
     * @coversNothing
     * @return void
     */
    public function test_config_stored_and_retrieved_correctly(): void {
        $json = json_encode(['qtype_essay' => ['qtype_essay_options' => ['fields' => ['graderinfo' => null]]]]);
        set_config('additionalconf', $json, 'local_deepler');
        $stored = get_config('local_deepler', 'additionalconf');
        $this->assertEquals($json, $stored);
    }

    /**
     * get_config returns false when additionalconf has never been stored.
     *
     * @coversNothing
     * @return void
     */
    public function test_config_returns_false_when_not_set(): void {
        unset_config('additionalconf', 'local_deepler');
        $result = get_config('local_deepler', 'additionalconf');
        $this->assertFalse($result);
    }

    /**
     * Admin can overwrite the stored config and the new value is immediately returned.
     *
     * @coversNothing
     * @return void
     */
    public function test_admin_overwrite_is_immediately_visible(): void {
        $original = json_encode(['key' => 'original']);
        $updated  = json_encode(['key' => 'updated', 'new_key' => 'new_value']);

        set_config('additionalconf', $original, 'local_deepler');
        $this->assertEquals($original, get_config('local_deepler', 'additionalconf'));

        set_config('additionalconf', $updated, 'local_deepler');
        $this->assertEquals($updated, get_config('local_deepler', 'additionalconf'));
    }
}
