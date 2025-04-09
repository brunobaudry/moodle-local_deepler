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
 * @file       amd/src/local/ui_mlangremover.js
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/log',
        'core/modal',
        './selectors',
        './translation',
        './utils',
        './customevents'
    ],
    (Log, Modal, Selectors, Translation, Utils, Events) => {
        const registerUI = ()=> {

        };

        /**
         *
         * @param {event} e
         */
        const handleChangeEvent = (e) => {
            Log.debug(`ui_mlangremover/x/handleChangeEvent`);
            Log.debug(e.target);
            Log.debug(e.target.classList);
            Log.debug(e.target.closest(Selectors.actions.allMLangCkboxesNames));
            if (e.target.closest(Selectors.actions.allMLangCkboxesNames)) {
                Log.debug(`ui_mlangremover/x/handleChangeEvent::allMLangCkboxesNames`);
            }
        };

        /**
         *
         * @param {event} e
         */
        const handleClickEvent = (e) => {
            Log.debug(`ui_mlangremover/x/handleClickEvent`);
            Log.debug(e.target);
            Log.debug(e.target.classList);
            Log.debug(e.target.closest(Selectors.actions.allMLangCkboxesNames));
            if (e.target.closest(Selectors.actions.checkBoxes)) {
                Log.debug(`ui_mlangremover/x/handleClickEvent::Selectors.actions.`);
            }
            if (e.target.closest(Selectors.actions.allMLangCkboxesNames)) {
                Log.debug(`ui_mlangremover/x/handleClickEvent::allMLangCkboxesNames`);
            }
        };

        /**
         * Event factories.
         */
        const registerEventListeners = () => {
            document.addEventListener('change', handleChangeEvent);
            document.addEventListener('click', handleClickEvent);
        };

        const toggleMlangs = ()=> {
            Log.debug(`ui_mlangremover/x/toggleMlangs::toggleMlangs`);
        };

        const init = (cfg)=>{
            Translation.init(cfg);
            registerUI();
            registerEventListeners();
            toggleMlangs();
            Utils.getCookie('mlangremover');
            Events.emit('mlangremover');
        };
        return {
            init: init
        };
    });
