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
 * @module     local_deepler/deepler
 * @file       amd/src/deepler.js
 * @copyright  2022 Kaleb Heitzman <kaleb@jamfire.io>
 * @copyright  2024 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['./local/ui_mlangremover', 'core/log'], (UI, Log) => {
    const debug = {
        NONE: 0, // Level 5 silent.
        MINIMAL: 5, // Level 3 no trace, debug or info.
        NORMAL: 15, // Level 2 no trace or debug.
        ALL: 30719, // Level 1 no trace.
        DEVELOPER: 32767 // Level 0 all.
    };
    const init = (cfg) => {
        let level = 5;
        switch (cfg.debug) {
            case debug.NONE : level = 5; break;
            case debug.MINIMAL : level = 3; break;
            case debug.NORMAL : level = 2; break;
            case debug.ALL : level = 1; break;
            case debug.DEVELOPER : level = 0; break;
        }
        Log.setConfig({level: level});
        Log.info(`09.04.2025 : 15:08 ` + cfg.version);
        window.addEventListener("DOMContentLoaded", UI.init(cfg));
    };
    return {
        init: init
    };
});
