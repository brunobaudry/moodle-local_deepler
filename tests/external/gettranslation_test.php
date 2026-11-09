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

require_once(__DIR__ . '/base_external.php');

use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use DeepL\AppInfo;
use DeepL\AuthorizationException;
use DeepL\DeepLClient;
use DeepL\DeepLException;
use DeepL\QuotaExceededException;
use DeepL\TextResult;
use ReflectionClass;

/**
 * PHPUnit tests for get_translation external service.
 *
 * @package    local_deepler
 * @category   test
 * @copyright  2025 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class gettranslation_test extends base_external {
    /**
     * Test execute_parameters method.
     *
     * @covers \local_deepler\external\get_translation::execute_parameters
     * @covers \local_deepler\external\get_rephrase::execute_parameters
     * @return void
     */
    public function test_execute_parameters(): void {
        if ($this->is_below_four_one()) {
            return;
        }
        $params = get_translation::execute_parameters();
        $paramsreph = get_rephrase::execute_parameters();
        $this->assertInstanceOf(external_function_parameters::class, $params);
        $this->assertInstanceOf(external_function_parameters::class, $paramsreph);
    }

    /**
     * Test execute_returns method.
     *
     * @covers \local_deepler\external\get_translation::execute_returns
     * @return void
     */
    public function test_executereturns(): void {
        if ($this->is_below_four_one()) {
            return;
        }
        $returns = get_translation::execute_returns();
        $returnsrephraz = get_rephrase::execute_returns();
        $this->assertInstanceOf(external_multiple_structure::class, $returns);
        $this->assertInstanceOf(external_multiple_structure::class, $returnsrephraz);
    }

    /**
     * Test setdeeplapikey method.
     *
     * @return void
     * @covers \local_deepler\external\get_translation::setdeeplapikey
     * @throws \dml_exception
     */
    public function test_setdeeplapikey(): void {
        if ($this->is_below_four_one()) {
            return;
        }
        // Set up the environment.
        $this->resetAfterTest(true);

        // Set the environment variable.
        putenv('DEEPL_APIKEY=testapikey');
        set_config('apikey', 'testapikey', 'local_deepler');

        // Call the method.
        $translator = get_translation::setdeeplapikey('v1.0');

        // Assert the API key is returned correctly.
        $this->assertInstanceOf(DeepLClient::class, $translator);
    }

    /**
     * Test setdeeplappinfo method.
     *
     * @return void
     * @covers \local_deepler\external\get_translation::setdeeplappinfo
     */
    public function test_setdeeplappinfo(): void {
        if ($this->is_below_four_one()) {
            return;
        }
        // Set up the environment.
        $this->resetAfterTest(true);
        // Call the method.
        $appinfo = get_translation::setdeeplappinfo('1.0');
        $appinforephraz = get_rephrase::setdeeplappinfo('1.0');

        // Assert the AppInfo object is created correctly.
        $this->assertInstanceOf(AppInfo::class, $appinfo);
        $this->assertInstanceOf(AppInfo::class, $appinforephraz);
        $this->assertEquals('Moodle-Deepler', $appinfo->appName);
        $this->assertEquals('Moodle-Deepler', $appinforephraz->appName);
        $this->assertEquals('1.0', $appinfo->appVersion);
        $this->assertEquals('1.0', $appinforephraz->appVersion);
    }

    /**
     * Correct split.
     *
     * @covers \local_deepler\external\get_translation::chunk_payload
     * @covers \local_deepler\external\get_rephrase::chunk_payload
     * @return void
     */
    public function test_chunkpayloadsplitsitemscorrectly(): void {
        $staticparts = ['partA' => 'value1', 'partB' => 'value2'];
        $items = [];
        // Each 'text' is 35,000 bytes, so >2 will exceed the chunk size.
        for ($i = 0; $i < 4; $i++) {
            $items[] = ['text' => str_repeat('a', 35000)];
        }
        // Call the static chunk_payload directly.
        $result = self::callprotectedstaticmethod(
            get_translation::class,
            'chunk_payload',
            [$items, $staticparts]
        );
        // Expect 2 chunks: first includes 3 items, second has 1 item.
        $this->assertCount(2, $result);
        $this->assertCount(2, $result[1]);
    }

    /**
     * Reflection helper.
     *
     * @param mixed $class
     * @param string $methodname
     * @param array $args
     * @return mixed
     * @throws \ReflectionException
     */
    protected static function callprotectedstaticmethod(mixed $class, string $methodname, array $args = []): mixed {
        $reflectionclass = new ReflectionClass($class);
        $method = $reflectionclass->getMethod($methodname);
        $method->setAccessible(true);
        // For static methods, pass null as the object.
        return $method->invokeArgs(null, $args);
    }

    /**
     * Loads empty item.
     *
     * @covers \local_deepler\external\get_translation::chunk_payload
     * @covers \local_deepler\external\get_rephrase::chunk_payload
     * @return void
     */
    public function test_chunkpayloadhandlesemptyitems(): void {
        $staticparts = ['foo' => 'bar'];
        $items = [];
        // Call the static chunk_payload directly.
        $result = self::callprotectedstaticmethod(
            get_translation::class,
            'chunk_payload',
            [$items, $staticparts]
        );
        $this->assertCount(0, $result);
    }

    /**
     * Respects max bytes for large payload.
     *
     * @covers \local_deepler\external\get_translation::chunk_payload
     * @covers \local_deepler\external\get_rephrase::chunk_payload
     * @return void
     */
    public function test_chunkpayloadrespectsmaxbytesforlargepayloads(): void {
        $staticparts = ['meta' => 'info'];
        $items = [
            ['text' => str_repeat('x', 100000)],
            ['text' => 'y'],
        ];
        // The first 'text' exceeds $maxbytes, should yield separate chunk per item.
        $result = self::callprotectedstaticmethod(
            get_translation::class,
            'chunk_payload',
            [$items, $staticparts]
        );
        $this->assertCount(2, $result);
        $this->assertCount(1, $result[1]);
    }

    /**
     * Texts with several top-level nodes get a single root and lose it again after translation.
     *
     * @covers \local_deepler\external\get_translation::wrap_html
     * @covers \local_deepler\external\get_translation::unwrap_html
     * @return void
     */
    public function test_wrap_and_unwrap_html(): void {
        $text = "<!-- A -->\r\n<div>Hallo</div>\r\n<!-- B -->\r\n<div>Welt</div>";
        $wrapped = self::callprotectedstaticmethod(get_translation::class, 'wrap_html', [$text]);
        $this->assertEquals('<div data-deepler-wrap="">' . $text . '</div>', $wrapped);
        $this->assertEquals($text, self::callprotectedstaticmethod(get_translation::class, 'unwrap_html', [$wrapped]));
        // DeepL may drop the empty attribute value or add surrounding whitespace.
        $unwrapped = self::callprotectedstaticmethod(
            get_translation::class,
            'unwrap_html',
            ["\n<div data-deepler-wrap><p>x</p></div>\n"]
        );
        $this->assertEquals('<p>x</p>', $unwrapped);
        // Unknown shapes are returned untouched.
        $this->assertEquals('<p>x</p>', self::callprotectedstaticmethod(get_translation::class, 'unwrap_html', ['<p>x</p>']));
    }

    /**
     * Only plain HTTP 400 errors trigger the one-by-one retry.
     *
     * @covers \local_deepler\external\get_translation::is_bad_request
     * @return void
     */
    public function test_is_bad_request(): void {
        $check = fn($e) => self::callprotectedstaticmethod(get_translation::class, 'is_bad_request', [$e]);
        $this->assertTrue($check(new DeepLException("Bad request, message: Tag handling parsing failed")));
        $this->assertFalse($check(new DeepLException('Service unavailable')));
        $this->assertFalse($check(new QuotaExceededException('Bad request')));
        $this->assertFalse($check(new AuthorizationException('Authorization failure')));
    }

    /**
     * A failing chunk is retried text by text, so only the malformed text reports an error.
     *
     * @covers \local_deepler\external\get_translation::process_chunk
     * @return void
     */
    public function test_process_chunk_isolates_bad_text(): void {
        $translator = $this->createMock(DeepLClient::class);
        $translator->method('translateText')->willReturnCallback(function ($texts) {
            if (in_array('BAD', $texts)) {
                throw new DeepLException("Bad request, message: Tag handling parsing failed, please check input.");
            }
            return array_map(fn($t) => new TextResult(str_replace(['>a<', '>c<'], ['>A<', '>C<'], $t), 'DE', strlen($t)), $texts);
        });
        $chunk = [
            ['text' => '<div data-deepler-wrap="">a</div>', 'key' => 'k1'],
            ['text' => 'BAD', 'key' => 'k2'],
            ['text' => '<div data-deepler-wrap="">c</div>', 'key' => 'k3'],
        ];
        $result = self::callprotectedstaticmethod(
            get_translation::class,
            'process_chunk',
            [$translator, $chunk, 'de', 'vi', ['tag_handling' => 'html'], '']
        );
        $this->assertCount(3, $result);
        $this->assertEquals(['k1', 'k2', 'k3'], array_column($result, 'key'));
        $this->assertEquals('A', $result[0]['translated_text']);
        $this->assertEquals('', $result[0]['error']);
        $this->assertStringContainsString('[k2]', $result[1]['error']);
        $this->assertEquals('C', $result[2]['translated_text']);
    }

    /**
     * Test execute_parameters validation with row options.
     *
     * @covers \local_deepler\external\get_translation::execute_parameters
     * @return void
     */
    public function test_execute_parameters_with_row_options(): void {
        if ($this->is_below_four_one()) {
            return;
        }

        $params = [
            'translations' => [
                [
                    'text' => 'Hello',
                    'source_lang' => 'en',
                    'key' => 'row_1',
                    'options' => [
                        'formality' => 'more',
                        'split_sentences' => 'nonewlines',
                        'preserve_formatting' => true,
                        'context' => 'Academic context',
                        'tag_handling' => 'html',
                        'outline_detection' => false,
                        'non_splitting_tags' => 'span',
                        'splitting_tags' => 'p',
                        'ignore_tags' => 'code',
                        'glossary_id' => 'glossary-123',
                        'model_type' => 'prefer_quality_optimized',
                    ],
                ],
                [
                    'text' => 'World',
                    'source_lang' => 'en',
                    'key' => 'row_2',
                ],
            ],
            'options' => [
                'target_lang' => 'DE',
                'context' => '',
                'tag_handling' => 'html',
                'split_sentences' => '1',
                'preserve_formatting' => false,
                'formality' => 'default',
                'outline_detection' => false,
                'non_splitting_tags' => '',
                'splitting_tags' => '',
                'ignore_tags' => '',
                'glossary_id' => '',
                'model_type' => 'default',
                'show_billed_characters' => true,
            ],
            'version' => 'v1.0',
        ];

        $validated = \core_external\external_api::validate_parameters(
            get_translation::execute_parameters(),
            $params
        );

        $this->assertCount(2, $validated['translations']);
        $this->assertEquals('more', $validated['translations'][0]['options']['formality']);
        $this->assertEquals('glossary-123', $validated['translations'][0]['options']['glossary_id']);
        $this->assertNull($validated['translations'][1]['options']['formality']);
    }

    /**
     * Test get_rephrase execute_parameters validation with row options and global toneorstyle.
     *
     * @covers \local_deepler\external\get_rephrase::execute_parameters
     * @return void
     */
    public function test_get_rephrase_execute_parameters_with_row_options(): void {
        if ($this->is_below_four_one()) {
            return;
        }

        $params = [
            'rephrasings' => [
                [
                    'text' => 'Hello there',
                    'key' => 'row_1',
                    'options' => [
                        'toneorstyle' => 'tone|prefer_friendly',
                    ],
                ],
                [
                    'text' => 'General Kenobi',
                    'key' => 'row_2',
                ],
            ],
            'options' => [
                'target_lang' => 'EN',
                'toneorstyle' => 'tone|prefer_diplomatic',
            ],
            'version' => 'v1.0',
        ];

        $validated = \core_external\external_api::validate_parameters(
            get_rephrase::execute_parameters(),
            $params
        );

        $this->assertCount(2, $validated['rephrasings']);
        $this->assertEquals('tone|prefer_friendly', $validated['rephrasings'][0]['options']['toneorstyle']);
        $this->assertNull($validated['rephrasings'][1]['options']['toneorstyle']);
        $this->assertEquals('tone|prefer_diplomatic', $validated['options']['toneorstyle']);
    }

    /**
     * Test get_rephrase execute_parameters validation with correct toneorstyle option.
     *
     * @covers \local_deepler\external\get_rephrase::execute_parameters
     * @return void
     */
    public function test_get_rephrase_execute_parameters_with_correct_option(): void {
        if ($this->is_below_four_one()) {
            return;
        }

        $params = [
            'rephrasings' => [
                [
                    'text' => 'This are bad grammar.',
                    'key' => 'row_1',
                    'options' => [
                        'toneorstyle' => 'correct',
                    ],
                ],
            ],
            'options' => [
                'target_lang' => 'en-US',
                'toneorstyle' => 'correct',
            ],
            'version' => 'v1.0',
        ];

        $validated = \core_external\external_api::validate_parameters(
            get_rephrase::execute_parameters(),
            $params
        );

        $this->assertCount(1, $validated['rephrasings']);
        $this->assertEquals('correct', $validated['rephrasings'][0]['options']['toneorstyle']);
        $this->assertEquals('correct', $validated['options']['toneorstyle']);
    }
}
