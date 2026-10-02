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

/**
 * Tests for the additionalconf upgrade helpers (db/upgradelib.php).
 *
 * @package    local_deepler
 * @category   test
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class upgradelib_test extends advanced_testcase {
    /**
     * Load the helpers.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        global $CFG;
        require_once($CFG->dirroot . '/local/deepler/db/upgradelib.php');
        $this->resetAfterTest();
    }

    /**
     * Merge rules: bundled fills the gaps, admin values win, null clauses get the bundled clauses.
     *
     * @covers ::local_deepler_merge_additionalconf
     * @return void
     */
    public function test_merge_additionalconf(): void {
        $stored = [
            'mod_url' => ['url' => ['fields' => ['name' => null, 'externalurl' => ['editable' => true]]]],
            'mod_custom' => ['custom' => ['fields' => ['title' => null]]],
            'mod_data' => ['data' => ['fields' => ['name' => null, 'singletemplate' => null, 'intro' => ['editable' => false]]]],
        ];
        $bundled = [
            'mod_url' => ['url' => ['fields' => ['name' => null, 'intro' => null, 'externalurl' => ['editable' => false]]]],
            'mod_data' => ['data' => ['fields' => [
                'name' => null, 'intro' => null,
                'singletemplate' => ['editable' => false], 'listtemplate' => ['editable' => false],
            ]]],
        ];
        $merged = local_deepler_merge_additionalconf($stored, $bundled);

        // Admin only component kept.
        $this->assertSame(['custom' => ['fields' => ['title' => null]]], $merged['mod_custom']);
        // Missing field added, admin clause kept.
        $this->assertArrayHasKey('intro', $merged['mod_url']['url']['fields']);
        $this->assertTrue($merged['mod_url']['url']['fields']['externalurl']['editable']);
        // New field added, null clause replaced by bundled clauses, explicit admin clause kept.
        $this->assertSame(['editable' => false], $merged['mod_data']['data']['fields']['listtemplate']);
        $this->assertSame(['editable' => false], $merged['mod_data']['data']['fields']['singletemplate']);
        $this->assertSame(['editable' => false], $merged['mod_data']['data']['fields']['intro']);
        $this->assertNull($merged['mod_data']['data']['fields']['name']);
        // Idempotent.
        $this->assertSame($merged, local_deepler_merge_additionalconf($merged, $bundled));
    }

    /**
     * Sync: seeds when empty/invalid, merges when valid, no-op when already up to date.
     *
     * @covers ::local_deepler_sync_additionalconf
     * @return void
     */
    public function test_sync_additionalconf(): void {
        global $CFG;
        $bundled = json_decode(file_get_contents($CFG->dirroot . '/local/deepler/additional_conf.json'), true);

        unset_config('additionalconf', 'local_deepler');
        $this->assertTrue(local_deepler_sync_additionalconf());
        $this->assertSame($bundled, json_decode(get_config('local_deepler', 'additionalconf'), true));
        $this->assertFalse(local_deepler_sync_additionalconf());

        set_config('additionalconf', 'not json {', 'local_deepler');
        $this->assertTrue(local_deepler_sync_additionalconf());
        $this->assertSame($bundled, json_decode(get_config('local_deepler', 'additionalconf'), true));

        $old = $bundled;
        unset($old['mod_data']);
        $old['mod_mine'] = ['mine' => ['fields' => ['name' => null]]];
        set_config('additionalconf', json_encode($old), 'local_deepler');
        $this->assertTrue(local_deepler_sync_additionalconf());
        $new = json_decode(get_config('local_deepler', 'additionalconf'), true);
        $this->assertSame($bundled['mod_data'], $new['mod_data']);
        $this->assertSame($old['mod_mine'], $new['mod_mine']);
        $this->assertFalse(local_deepler_sync_additionalconf());
    }
}
