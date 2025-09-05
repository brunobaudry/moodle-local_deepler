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
 * @file       amd/src/local/ui_deepler.js
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


define(['core/log',
    './translation',
    './scrollspy',
    './eventHandlers',
    './uiHelpers',
    './settings'
], (Log,
    Translation,
    ScrollSpy,
    EventHandler,
    UI,
Settings) => {

    /**
     * Event listener to switch source lang.
     * @param {*} cfg
     */
    const init = (cfg) => {
        ScrollSpy.init('.local_deepler__form', '#local_deepler-scrollspy',
            {
                        highestLevel: 3,
                        fadingDistance: 60,
                        offsetEndOfScope: 1,
                        offsetTop: 100,
                        crumbsmaxlen: cfg.crumbsmaxlen
                    }
        );
        Settings.init(cfg);
        Translation.init(cfg);
        UI.init(cfg);
        EventHandler.init(cfg);
        Log.info(cfg);
    };
    /**
     * Api to be used by the other modules.
     */
    return {
        init: init
    };
});
