<?php

namespace local_deepler\local\data\subs\questions;

use database_manager;
use local_deepler\local\data\field;
use question_definition;
use question_type;

abstract class qbase {
    private database_manager $dbmanager;
    private question_type $qtype;
    private question_definition $question;

    public function __construct(question_definition $q) {
        global $DB;
        $this->question = $q;
        $this->dbmanager = $DB->get_manager(); // Get the database manager.
        $this->qtype = $q->qtype;
    }

    public function getmain() {
        $columns = field::filterdbtextfields('question');
        return field::getfields($this->question, 'question', $columns);
    }
}
