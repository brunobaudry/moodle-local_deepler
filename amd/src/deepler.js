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
define(['./local/ui_deepler', 'core/log'], (UI, Log) => {
    const debug = {
        NONE: 0, // Level 5 silent.
        MINIMAL: 5, // Level 3 no trace, debug or info.
        NORMAL: 15, // Level 2 no trace or debug.
        ALL: 30719, // Level 1 no trace.
        DEVELOPER: 32767 // Level 0 all.
    };
    const init = (cfg) => {
        const levelMap = {
            [debug.NONE]: 5,
            [debug.MINIMAL]: 3,
            [debug.NORMAL]: 2,
            [debug.ALL]: 1,
            [debug.DEVELOPER]: 0
        };
        const level = levelMap[cfg.debug] ?? 5;
        Log.setConfig({level});
        Log.info(`09.04.2025 : 15:08 ` + cfg.version);

        let activeRequests = 0;
        let ajaxStopFired = false;

        const onAjaxStop = ()=> {
            // This runs only once when all AJAX requests are finished after a batch
            const preloader = document.getElementById('local_deepler_preloaderModal');
            if (preloader) {
                preloader.classList.remove('show', 'd-block');
                preloader.classList.add('d-none');
            }
            ajaxStopFired = true;
        };

        const originalOpen = XMLHttpRequest.prototype.open;
        XMLHttpRequest.prototype.open = function(...args) {
            activeRequests++;
            ajaxStopFired = false; // New requests reset the flag
            this.addEventListener('readystatechange', function() {
                if (this.readyState === 4) { // Request completed
                    activeRequests--;
                    if (activeRequests === 0 && !ajaxStopFired) {
 onAjaxStop();
}
                }
            }, false);
            originalOpen.apply(this, args);
        };


        // Window.addEventListener("DOMContentLoaded", UI.init(cfg));
        window.addEventListener('load', UI.init(cfg));
    };
    return {
        init: init
    };
});
