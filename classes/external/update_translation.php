<?php

namespace local_deepler\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

class update_translation extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
                'data' => new external_multiple_structure(
                        new external_single_structure([
                                'courseid' => new external_value(PARAM_INT, 'The course to fetch the field from'),
                                'id' => new external_value(PARAM_INT, 'The id of the course field'),
                                'tid' => new external_value(PARAM_INT, 'The id of the activity table'),
                                'table' => new external_value(PARAM_ALPHANUMEXT, 'The table name'),
                                'field' => new external_value(PARAM_ALPHANUMEXT, 'The field name'),
                                'text' => new external_value(PARAM_RAW, 'The new text content with multilang2 translations'),
                        ])
                )
        ]);
    }

    public static function execute($data) {
        global $CFG, $DB;
        $params = self::validate_parameters(self::execute_parameters(), ['data' => $data]);
        $transaction = $DB->start_delegated_transaction();
        $response = [];
        foreach ($params['data'] as $data) {
            purge_all_caches();
            // Security checks.
            $context = \context_course::instance($data['courseid']);
            self::validate_context($context);
            require_capability('local/deepler:edittranslations', $context);
            // Check detailed activity capabilities.
            require_capability('moodle/course:manageactivities', \context_module::instance($data['id']));
            // Update the record.
            $dataobject = [];
            $dataobject['id'] = $data['id'];
            $dataobject[$data['field']] = $data['text'];
            $DB->update_record($data['table'], (object) $dataobject);

            // Update t_lastmodified.
            $timemodified = time();
            $DB->update_record('local_deepler', ['id' => $data['tid'], 't_lastmodified' => $timemodified]);

            $response[] = ['t_lastmodified' => $timemodified, 'text' => $data['text']];
        }
        // Commit the transaction.
        $transaction->allow_commit();
        return $response;

    }

    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
                new external_single_structure([
                        't_lastmodified' => new external_value(PARAM_INT, 'Timestamp the field was modified'),
                        'text' => new external_value(PARAM_RAW, 'The updated text content'),
                ])
        );
    }
}
