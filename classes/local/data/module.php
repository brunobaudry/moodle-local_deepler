<?php

namespace local_deepler\local\data;

use cm_info;

class module {
    private cm_info $cm;

    public function __construct(cm_info $cminfo) {
        $this->cm = $cminfo;
        var_dump($this->cm->modname);
        var_dump($this->isvisible());
    }

    public function isvisible(): bool {
        return $this->cm->visible == true;
    }
}
