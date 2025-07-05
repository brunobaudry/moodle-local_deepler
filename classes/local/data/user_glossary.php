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

/**
 * User glossary model for the local_deepler plugin.
 *
 * This class provides CRUD operations for the local_deepler_user_glossary table.
 *
 * @package    local_deepler
 * @category   model
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_deepler\local\data;

use coding_exception;
use stdClass;

/**
 * Class user_glossary
 *
 * Represents a mapping between a user and a glossary, and provides static CRUD operations.
 */
class user_glossary {
    /** @var string Database table name */
    const TABLE = 'local_deepler_user_glossary';

    /** @var int|null Primary key (optional for new records) */
    public ?int $id = null;

    /** @var int Moodle user ID */
    public int $userid;

    /** @var int table ID of the glossary (not DeepL's) */
    public int $glossaryid;

    /** @var int Whether the glossary is active for the user (1 or 0) */
    public int $isactive;

    /**
     * Constructor to initialize a user_glossary object.
     *
     * @param int $userid
     * @param int $glossarydbid
     * @param int $isactive
     * @param int|null $id
     */
    public function __construct(int $userid, int $glossarydbid, int $isactive = 1, ?int $id = null) {
        $this->userid = $userid;
        $this->glossaryid = $glossarydbid;
        $this->isactive = $isactive;
        $this->id = $id;
    }

    /**
     * Creates a new user-glossary mapping in the database.
     *
     * @param self $mapping
     * @return int Inserted record ID
     */
    public static function create(self $mapping): int {
        global $DB;
        return $DB->insert_record(self::TABLE, $mapping->toobject());
    }

    /**
     * Converts the object to a stdClass for DB operations.
     *
     * @return \stdClass
     */
    public function toobject(): stdClass {
        $obj = (object) [
                'userid' => $this->userid,
                'glossaryid' => $this->glossaryid,
                'isactive' => $this->isactive,
        ];
        if ($this->id !== null) {
            $obj->id = $this->id;
        }
        return $obj;
    }

    /**
     * Retrieves a user-glossary mapping by ID.
     *
     * @param int $id
     * @return self
     */
    public static function getbyid(int $id): self {
        global $DB;
        $record = $DB->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);
        return new self(
                $record->userid,
                $record->glossaryid,
                $record->isactive,
                $record->id
        );
    }

    /**
     * Retrieves a user-glossary mapping by user ID and glossary ID.
     *
     * @param int $userid
     * @param int $glossaryid
     * @return self|null
     */
    public static function getbyuserandglossary(int $userid, int $glossaryid): ?self {
        global $DB;
        $record = $DB->get_record(self::TABLE, ['userid' => $userid, 'glossaryid' => $glossaryid]);
        if (!$record) {
            return null;
        }
        return new self(
                $record->userid,
                $record->glossaryid,
                $record->isactive,
                $record->id
        );
    }

    /**
     * Retrieves all glossary mappings for a user.
     *
     * @param int $userid
     * @return self[]
     */
    public static function getallbyuser(int $userid): array {
        global $DB;
        $records = $DB->get_records(self::TABLE, ['userid' => $userid]);
        $mappings = [];

        foreach ($records as $record) {
            $mappings[] = new self(
                    $record->userid,
                    $record->glossaryid,
                    $record->isactive,
                    $record->id
            );
        }

        return $mappings;
    }

    /**
     * Get all users of a glossary
     *
     * @param string $glossaryid
     * @return array
     * @throws \dml_exception
     */
    public static function getallbyglossary(string $glossaryid): array {
        global $DB;
        $records = $DB->get_records(self::TABLE, ['glossaryid' => $glossaryid]);
        $mappings = [];

        foreach ($records as $record) {
            $mappings[] = new self(
                    $record->userid,
                    $record->glossaryid,
                    $record->isactive,
                    $record->id
            );
        }

        return $mappings;
    }

    /**
     * Updates an existing user-glossary mapping.
     *
     * @param self $mapping
     * @return bool True on success
     * @throws \coding_exception|\dml_exception If ID is missing
     */
    public static function update(self $mapping): bool {
        global $DB;
        if ($mapping->id === null) {
            throw new coding_exception('Cannot update user_glossary: missing ID.');
        }
        return $DB->update_record(self::TABLE, $mapping->toobject());
    }

    /**
     * Deletes a user-glossary mapping by ID.
     *
     * @param int $id
     * @return bool True on success
     */
    public static function delete(int $id): bool {
        global $DB;
        return $DB->delete_records(self::TABLE, ['id' => $id]);
    }
}
