<?php

namespace local_deepler\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;

class get_field extends external_api {
    public static function execute_parameters(): \external_function_parameters {
        return new external_function_parameters([
                'data' => new \external_single_structure([
                        'courseid' => new external_value(PARAM_INT, 'The course to fetch the field from'),
                        'id' => new external_value(PARAM_INT, 'The id of the course field'),
                        'table' => new external_value(PARAM_ALPHANUMEXT, 'The id of the course field'),
                        'field' => new external_value(PARAM_ALPHANUMEXT, 'The id of the course field'),
                ])
        ]);
    }

    public static function execute($data): object {
        global $CFG, $DB;
        $params = self::validate_parameters(self::execute_parameters(), $data);
        $transaction = $DB->start_delegated_transaction();
        $response = [];
        foreach ($params['data'] as $data) {
            // Security checks.
            $context = context_course::instance($data['courseid']);
            self::validate_context($context);
            require_capability('local/deepler:edittranslations', $context);
        }
        return $response;

    }

    public static function execute_returns(): \external_single_structure {
        return new \external_single_structure([
                'text' => new external_value(PARAM_RAW, 'Fields text content')
        ]);
    }
}
