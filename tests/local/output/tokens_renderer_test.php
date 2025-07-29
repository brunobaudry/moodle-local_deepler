<?php

namespace local_deepler;

use advanced_testcase;
use local_deepler\output\tokens_renderer;

class tokens_renderer_test extends advanced_testcase {

    public function test_render_token_manager_contains_expected_keys() {
        global $SESSION;

        $this->resetAfterTest(true);

        $SESSION->local_deepler_errors = ['Sample error'];

        $renderer = new tokens_renderer();
        $output = $renderer->render_token_manager();

        $this->assertStringContainsString('Sample error', $output);
        $this->assertStringContainsString('tokenadd', $output); // Assuming tokenadd string is rendered
        $this->assertStringContainsString('choose', $output);   // Placeholder string
    }
}
