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

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use DeepL\Translator;

require_once(__DIR__ . '/../vendor/autoload.php');

class get_translation extends external_api {

    public static function execute($data) {
        $params = self::validate_parameters(self::execute_parameters(), ['data' => $data]);
        $translator = new Translator(get_config('local_deepler', 'apikey'), ['send_platform_info' => false]);
        $trad = $translator->translateText('Bonjour', 'fr', 'de');
        return [['test' => $trad->text]];
    }

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
                'data' => new external_multiple_structure(
                        new external_single_structure(
                                ['test' => new external_value(PARAM_ALPHANUMEXT, 'The id of the activity table')]))
        ]);
    }

    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
                new external_single_structure(
                        ['test' => new external_value(PARAM_RAW, 'test translation')]
                )
        );
    }

}
