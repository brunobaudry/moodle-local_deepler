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
 * Glossary model for the local_deepler plugin.
 *
 * This class provides CRUD operations for the local_deepler_glossaries table.
 *
 * @package    local_deepler
 * @category   model
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_deepler\local\data;

use coding_exception;
use phpDocumentor\Reflection\Types\Boolean;
use stdClass;

/**
 * Class glossary
 *
 * Represents a DeepL glossary and provides static CRUD operations.
 */
class glossary {
    /** @var string Database table name */
    const TABLE = 'local_deepler_glossaries';

    /** @var int|null Primary key (optional for new records) */
    public ?int $id = null;

    /** @var string DeepL glossary ID */
    public string $glossaryid;

    /** @var string Glossary name */
    public string $name;

    /** @var string sourcelang language code (e.g., 'en') */
    public string $sourcelang;

    /** @var string Target language code (e.g., 'de') */
    public string $targetlang;
    /** @var int|null shared status (0=private,1=pool,2=public) */
    public int $shared;
    /** @var int the db token ID the glossary was created with (for pool sharing) */
    public int $tokenid;
    /** @var int Unix timestamp of creation */
    public int $timecreated;
    /** @var int Unix timestamp of last usage */
    public int $lastused;
    /** @var int entrycount */
    public int $entrycount;

    /**
     * Constructor to initialize a glossary object.
     *
     * @param string $glossaryid
     * @param string $name
     * @param string $sourcelang
     * @param string $target
     * @param int $entrycount
     * @param int|null $tokenid
     * @param int|null $shared
     * @param int|null $timecreated
     * @param int|null $lastused
     * @param int|null $id
     */
    public function __construct(
            string $glossaryid,
            string $name,
            string $sourcelang,
            string $target,
            int $entrycount,
            ?int $tokenid = 0,
            ?int $shared = 0,
            ?int $timecreated = null,
            ?int $lastused = 0,
            ?int $id = null
    ) {
        $this->glossaryid = $glossaryid;
        $this->name = $name;
        $this->sourcelang = $sourcelang;
        $this->targetlang = $target;
        $this->entrycount = $entrycount;
        $this->shared = $shared;
        $this->tokenid = $tokenid;
        $this->timecreated = $timecreated ?? time();
        $this->lastused = $lastused ?? 0;
        $this->id = $id;
    }

    /**
     * Creates a new glossary record in the database.
     *
     * @param self $glossary
     * @return int Inserted record ID
     */
    public static function create(self $glossary): int {
        global $DB;
        return $DB->insert_record(self::TABLE, $glossary->toobject());
    }

    /**
     * Converts the object to a stdClass for DB operations.
     *
     * @return \stdClass
     */
    public function toobject(): stdClass {
        $obj = (object) [
                'glossaryid' => $this->glossaryid,
                'name' => $this->name,
                'sourcelang' => $this->sourcelang,
                'targetlang' => $this->targetlang,
                'entrycount' => $this->entrycount,
                'timecreated' => $this->timecreated,
                'lastused,' => $this->lastused,
                'shared' => $this->shared,
                'tokenid' => $this->tokenid,
        ];
        if ($this->id !== null) {
            $obj->id = $this->id;
        }
        return $obj;
    }

    /**
     * Retrieves a glossary record by ID and returns a glossary object.
     *
     * @param int $id
     * @return self
     * @throws \dml_exception
     */
    public static function getbyid(int $id): self {
        global $DB;
        $record = $DB->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);
        return new self(
                $record->glossaryid,
                $record->name,
                $record->sourcelang,
                $record->targetlang,
                $record->entrycount,
                $record->tokenid,
                $record->shared,
                $record->timecreated,
                $record->lastused,
                $record->id
        );
    }

    /**
     * @param int $tokenid
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function getpublicexcepttokenid(int $tokenid): array {
        global $DB;
        $glossaries = [];
        list($notin_sql, $params) = $DB->get_in_or_equal($tokenid, SQL_PARAMS_NAMED, 'tokenid', false);
        $select = "shared = :shared AND tokenid $notin_sql";
        $params['shared'] = 2;
        $records = $DB->get_records_select(self::TABLE, $select, $params);
        foreach ($records as $record) {
            $glossaries[] = new self(
                    $record->glossaryid,
                    $record->name,
                    $record->sourcelang,
                    $record->targetlang,
                    $record->entrycount,
                    $record->tokenid,
                    $record->shared,
                    $record->timecreated,
                    $record->lastused,
                    $record->id
            );
        }
        return $glossaries;
    }
    /**
     * Retrieves a glossary record by ID and returns a glossary object.
     *
     * @param int $glossaryid
     * @return self
     * @throws \dml_exception
     */
    public static function getbyglossaryid(int $glossaryid): self {
        global $DB;
        $record = $DB->get_record(self::TABLE, ['glossaryid' => $glossaryid], '*', MUST_EXIST);
        return new self(
                $record->glossaryid,
                $record->name,
                $record->sourcelang,
                $record->targetlang,
                $record->entrycount,
                $record->tokenid,
                $record->shared,
                $record->timecreated,
                $record->lastused,
                $record->id
        );
    }

    /**
     * @param int $tokenid
     * @return array
     * @throws \dml_exception
     */
    public static function getallbytokenid(int $tokenid): array {
        global $DB;
        $glossaries = [];
        $records = $DB->get_records(self::TABLE, ['tokenid' => $tokenid], '');
        if ($records) {
            foreach ($records as $record) {
                $glossaries[] = new self(
                        $record->glossaryid,
                        $record->name,
                        $record->sourcelang,
                        $record->targetlang,
                        $record->entrycount,
                        $record->tokenid,
                        $record->shared,
                        $record->timecreated,
                        $record->lastused,
                        $record->id);
            }
        }
        return $glossaries;
    }

    /**
     * Retrieves all glossary records as glossary objects.
     *
     * @return self[]
     * @throws \dml_exception
     */
    public static function getall(?string $sourcelang, ?string $targetlang): array {
        global $DB;
        $records = $DB->get_records(self::TABLE);
        $glossaries = [];

        foreach ($records as $record) {
            if ($sourcelang !== null && $sourcelang !== $record->sourcelang) {
                continue;
            }
            if ($targetlang !== null && $targetlang !== $record->targetlang) {
                continue;
            }
            $glossaries[] = new self(
                    $record->glossaryid,
                    $record->name,
                    $record->sourcelang,
                    $record->targetlang,
                    $record->entrycount,
                    $record->tokenid,
                    $record->shared,
                    $record->timecreated,
                    $record->lastused,
                    $record->id
            );
        }

        return $glossaries;
    }

    /**
     * Updates an existing glossary record.
     *
     * @param self $glossary
     * @return bool True on success
     * @throws \coding_exception|\dml_exception If ID is missing
     */
    public static function update(self $glossary): bool {
        global $DB;
        if ($glossary->id === null) {
            throw new coding_exception('Cannot update glossary: missing ID.');
        }
        return $DB->update_record(self::TABLE, $glossary->toobject());
    }

    /**
     * Deletes a glossary record by ID.
     *
     * @param int $id
     * @return bool True on success
     */
    public static function delete(int $id): bool {
        global $DB;
        return $DB->delete_records(self::TABLE, ['id' => $id]);
    }

    /**
     * Checks if a DeepL glossary ID exists in DB.
     *
     * @param string $deeplid
     * @return bool
     * @throws \dml_exception
     */
    public static function exists(string $deeplid): bool {
        global $DB;
        return $DB->record_exists(self::TABLE, ['glossaryid' => $deeplid]);

    }
}
