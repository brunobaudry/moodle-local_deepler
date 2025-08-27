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

namespace local_deepler;

use advanced_testcase;
use context_system;
use core_filters\text_filter;
use local_deepler\output\tokens_renderer;

/**
 * User tokens_renderer test case.
 *
 * @package    local_deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_deepler\output\tokens_renderer
 */
final class tokens_renderer_test extends advanced_testcase {
    /**
     * Test renderer.
     *
     * @return void
     * @throws \coding_exception
     * @throws \core\exception\moodle_exception
     * @throws \dml_exception
     */
    public function test_render_token_manager_contains_expected_keys(): void {
        global $SESSION, $PAGE;
        filter_set_global_state('multilang2', TEXTFILTER_ON);
        set_config('apikey', '11111111-2222-3333-4444-555555555555', 'local_deepler');
        $this->resetAfterTest(true);

        $SESSION->local_deepler_errors = ['Sample error'];
        $renderer = $PAGE->get_renderer('local_deepler', 'tokens');

        $output = $renderer->render_token_manager(context_system::instance());

        $this->assertStringContainsString('Sample error', $output);
        $this->assertStringContainsString('addtoken', $output); // Assuming tokenadd string is rendered.
        $this->assertStringContainsString('Choose', $output);   // Placeholder string.
    }
}
