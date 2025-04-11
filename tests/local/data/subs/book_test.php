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

namespace local_deepler\local\data\subs;
defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_deepler\local\data\field;

global $CFG;
require_once($CFG->dirroot . '/mod/book/locallib.php');

/**
 * Unit tests for book class.
 *
 * @package    local_deepler
 * @covers     \local_deepler\local\data\subs\book
 * @copyright  2025 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class book_test extends advanced_testcase {

    /**
     * Test the constructor and getfields method.
     *
     * @return void
     * @covers \local_deepler\local\data\subs\book::__construct
     */
    public function test_constructor_and_getfields(): void {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $book = $this->getDataGenerator()->create_module('book', ['course' => $course->id]);
        $bookgenerator = $this->getDataGenerator()->get_plugin_generator('mod_book');
        $bookgenerator->create_chapter(
                ['bookid' => $book->id, 'content' => 'Content 1', 'title' => 'Chapter 1']);
        $bookgenerator->create_chapter(
                ['bookid' => $book->id, 'content' => 'Content 2', 'title' => 'Chapter 2']);

        // Create an instance of the book class.
        $bookinstance = new book($book);

        // Test the getfields method.
        /** @var field[] $fields */
        $fields = $bookinstance->getfields();
        $this->assertCount(4, $fields);
        $this->assertEquals('Chapter 2', $fields[0]->get_text());
        $this->assertEquals('Content 2', $fields[1]->get_text());
        $this->assertEquals('Chapter 1', $fields[2]->get_text());
        $this->assertEquals('Content 1', $fields[3]->get_text());
        $this->assertEquals('book_chapters', $fields[0]->get_table());
        $this->assertEquals('chapter', $fields[0]->get_tablefield());
        $this->assertEquals('book_chapters', $fields[1]->get_table());
        $this->assertEquals('content', $fields[1]->get_tablefield());
        $this->assertEquals('book_chapters', $fields[2]->get_table());
        $this->assertEquals('chapter', $fields[2]->get_tablefield());
        $this->assertEquals('book_chapters', $fields[3]->get_table());
        $this->assertEquals('content', $fields[3]->get_tablefield());
    }
}
