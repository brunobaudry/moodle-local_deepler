<?php

namespace local_deepler\local\data;

use lang_string;

/**
 * Class multilanger.
 * String decorator with mlang functionalities.
 */
class multilanger {
    /**
     * @var string[]
     */
    static array $translatedfields = [];
    /**
     * @var string
     */
    private string $text;

    /**
     * Getter for main text
     *
     * @return string
     */
    public function get_text(): string {
        return $this->text;
    }

    /**
     * @param string $field
     */
    public function __construct(string $field) {
        $this->text = $field;
    }

    /**
     * As the title says.
     *
     * @return bool
     */
    public function has_multilang(): bool {
        return str_contains($this->text, '{mlang}');
    }

    /**
     * Checks if field contains a mlang translation for the given code.
     *
     * @param string $code
     * @return bool
     */
    public function hasmultilangcode(string $code): bool {
        return str_contains($this->text, "{mlang $code}");
    }

    /**
     * Get the codes availables in the multilang filter.
     *
     * @return int[]|string[]
     */
    public function findmlangcodes() {
        return array_keys($this->findmlangs());
    }

    /**
     * Update the field text
     *
     * @param string $code
     * @param string $text
     * @return void
     */
    private function updatemlang(string $code, string $text): void {
        $pattern = "/{mlang\s+{$code}\s*}(.*?){mlang\s*}/si";
        $this->text = preg_replace($pattern, "{mlang $code}$text{mlang}", $this->text);
    }

    /**
     * Only adds the mlang if it does not exist.
     *
     * @param string $code
     * @param string $text
     * @return void
     */
    public function update_or_add_mlang(string $code, string $text): void {
        if ($this->hasmultilangcode($code)) {
            $this->updatemlang($code, $text);
        } else {
            $this->addmlang_ifothers($code, $text);
        }
    }

    /**
     * Add a mlang tag to the field text.
     * Assuming the text filed already has mlangs but not this code's.
     *
     * @param string $code
     * @param string $text
     * @return void
     */
    private function addmlang_ifothers(string $code, string $text) {
        $needle = '{mlang}';
        $pos = strrpos($this->text, $needle);
        $update = "{mlang}{mlang $code}$text{mlang}";
        $this->text = substr_replace($this->text, $update, $pos, 0);
    }
    /**
     * Builds languages an array of string of iso codes or 'other'.
     *
     * @return array
     */
    public function findmlangs(): array {
        $pattern = "/({\s*mlang\s+(([a-z]{2}|other)(_[A-Za-z]{2})?)\s*}.*?{mlang\s*})/si";
        preg_match_all($pattern, $this->text, $matches);

        $result = [];
        foreach ($matches[2] as $index => $key) {
            $result[$key] = $matches[1][$index];
        }

        return $result;
    }

    /**
     * Get all mlang codes for collections of fields.
     *
     * @param array $fields
     * @return array
     */
    public static function langcodesforfields(array $fields): array {
        $mlangs = [];
        foreach ($fields as $field) {
            $ml = new multilanger($field->get_text());
            $codes = $ml->findmlangcodes();
            foreach ($codes as $code) {
                if (!in_array($code, $mlangs)) {
                    $mlangs[] = $code;
                }
            }
        }
        return $mlangs;
    }

    /**
     * @param \local_deepler\local\data\field $field
     * @return \lang_string|string
     * @throws \coding_exception
     */
    public static function findfieldstring(field $field): lang_string|string {
        $trkey = $field->get_table() . "#" . $field->get_tablefield();
        if (!isset(self::$translatedfields)) {
            self::$translatedfields = [];
        }
        if (!isset(self::$translatedfields[$trkey])) {
            // Create it if not cached.
            self::$translatedfields[$trkey] = self::search_field_strings($field);
        }
        return self::$translatedfields[$trkey];
    }

    /**
     * Try to find the string of each fields of mod/plugin.
     *
     * @param \local_deepler\local\data\field $field
     * @return string|\lang_string
     * @throws \coding_exception
     */
    private static function search_field_strings(field $field): string|lang_string {
        $f = $field->get_tablefield();
        $t = $field->get_table();
        // Try to find the activity names as well as the field translated in the current lang.
        if ($t === 'course') {
            return get_string($f);
        } else if ($t === 'course_sections') {
            if ($f === 'name') {
                return get_string('sectionname');
            } else if ($f === 'summary') {
                return get_string('description');
            } else {
                return '';
            }
        } else {
            if ($f === 'intro') {
                return get_string('description');
            } else if ($f === 'name') {
                return get_string('name');
            } else {
                // One should be better than the other.
                return self::findoutstanding($t, $f);
            }
        }
    }

    /**
     * Find the string in the Moodle database.
     *
     * @param string $ta
     * @param string $fi
     * @return string
     * @throws \coding_exception
     */
    private static function findoutstanding(string $ta, string $fi): string {
        $foundstring = $fi;

        // Extract plugin component from table name.
        $tableparts = explode('_', $ta, 2);
        $plugincomponent = isset($tableparts[1]) ? 'mod_' . $tableparts[0] : '';

        $candidates = [
                ['identifier' => $fi, 'component' => $plugincomponent], // Highest priority: Direct field name in plugin.
                ['identifier' => $fi, 'component' => 'core'], // Standard Moodle core strings.
                ['identifier' => $fi, 'component' => 'moodle'], // Standard Moodle core strings.
                ['identifier' => $fi, 'component' => 'question'], // Standard Moodle core strings.
                ['identifier' => $fi . 'n', 'component' => 'question'], // Standard Moodle core strings.
                ['identifier' => $fi, 'component' => $ta], // Standard Moodle core strings.
                ['identifier' => $ta . '_' . $fi, 'component' => $plugincomponent], // Common field patterns.
                ['identifier' => $ta . '_' . $fi, 'component' => 'core'], // Common field patterns.
                ['identifier' => $fi, 'component' => 'datafield_' . $fi], // Field type specific (data activity).
                ['identifier' => $ta . $fi, 'component' => $plugincomponent], // Legacy patterns.
        ];
        foreach ($candidates as $candidate) {
            if (empty($candidate['component'])) {
                continue;
            }
            if (get_string_manager()->string_exists($candidate['identifier'], $candidate['component'])) {
                return get_string($candidate['identifier'], $candidate['component']);
            }
        }

        return $foundstring;
    }

}
