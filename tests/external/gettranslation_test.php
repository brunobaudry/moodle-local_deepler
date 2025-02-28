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

namespace local_deepler\tests;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use DeepL\DeepLException;
use DeepL\Translator;
use invalid_parameter_exception;
use local_deepler\external\get_translation;

/**
 * PHPUnit tests for get_translation external service.
 *
 * @package    local_deepler
 * @category   test
 * @copyright  2025 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_translation_test extends advanced_testcase {

    /**
     * Test execute_parameters method.
     */
    public function test_execute_parameters() {
        $params = get_translation::execute_parameters();
        $this->assertInstanceOf(external_function_parameters::class, $params);
    }

    /**
     * Test execute_returns method.
     */
    public function test_execute_returns() {
        $returns = get_translation::execute_returns();
        $this->assertInstanceOf(external_multiple_structure::class, $returns);
    }

    /**
     * Test execute method with valid parameters.
     */
    public function test_execute_valid() {
        $this->resetAfterTest(true);

        $translations = [
                ['text' => 'Hello', 'source_lang' => 'EN', 'key' => 'greeting'],
                ['text' => 'World', 'source_lang' => 'EN', 'key' => 'noun']
        ];
        $options = [
                'target_lang' => 'DE',
                'context' => 'general',
                'tag_handling' => 'html',
                'split_sentences' => '1',
                'preserve_formatting' => true,
                'formality' => 'default',
                'outline_detection' => true,
                'non_splitting_tags' => '',
                'splitting_tags' => '',
                'ignore_tags' => '',
                'glossary_id' => '',
                'model_type' => '',
                'show_billed_characters' => false
        ];

        $result = get_translation::execute($translations, $options);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('key', $result[0]);
        $this->assertArrayHasKey('translated_text', $result[0]);
        $this->assertArrayHasKey('error', $result[0]);
    }

    /**
     * Test execute method with invalid parameters.
     */
    public function test_execute_invalid() {
        $this->resetAfterTest(true);

        $this->expectException(invalid_parameter_exception::class);

        $translations = 'invalid';
        $options = 'invalid';

        get_translation::execute($translations, $options);
    }

    /**
     * Test execute method with DeepLException.
     */
    public function test_execute_deepl_exception() {
        $this->resetAfterTest(true);

        $translations = [
                ['text' => 'Hello', 'source_lang' => 'EN', 'key' => 'greeting']
        ];
        $options = [
                'target_lang' => 'DE',
                'context' => 'general',
                'tag_handling' => 'html',
                'split_sentences' => '1',
                'preserve_formatting' => true,
                'formality' => 'default',
                'outline_detection' => true,
                'non_splitting_tags' => '',
                'splitting_tags' => '',
                'ignore_tags' => '',
                'glossary_id' => '',
                'model_type' => '',
                'show_billed_characters' => false
        ];

        // Mocking Translator class to throw DeepLException.
        $translator_mock = $this->createMock(Translator::class);
        $translator_mock->method('translateText')->will($this->throwException(new DeepLException('Test exception')));

        // Injecting mock into get_translation class.
        get_translation::set_translator($translator_mock);

        // Expecting DeepLException to be thrown.
        $this->expectException(DeepLException::class);

        get_translation::execute($translations, $options);
    }
}
