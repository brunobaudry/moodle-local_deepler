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
define([
    'core/log',
    'jquery',
        './local/translation',
        './local/scrollspy',
        './local/eventHandlers',
        './local/uiHelpers',
        './local/settings'
    ],
    (
     Log,
     $,

     Translation,

     ScrollSpy,

     EventHandler,

     UI,

     Settings
     ) => {
// Define(['./local/main', 'core/log', 'jquery'], (UI, Log, $) => {
    const debug = {
        NONE: 0, // Level 5 silent.
        MINIMAL: 5, // Level 3 no trace, debug or info.
        NORMAL: 15, // Level 2 no trace or debug.
        ALL: 30719, // Level 1 no trace.
        DEVELOPER: 32767 // Level 0 all.
    };
    let config;

        const launch = (cfg) => {
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
    const init = (cfg) => {
        config = cfg;
        const levelMap = {
            [debug.NONE]: 5,
            [debug.MINIMAL]: 3,
            [debug.NORMAL]: 2,
            [debug.ALL]: 1,
            [debug.DEVELOPER]: 0
        };
        const level = levelMap[config.debug] ?? 5;
        Log.setConfig({level});

        let activeRequests = 0;
        let ajaxStopFired = false;
        const preloader = document.getElementById('local_deepler_preloaderModal');
        /**
         * Remove preloader.
         */
        const onAjaxStop = ()=> {
            // This runs only once when all AJAX requests are finished after a batch
            if (preloader) {
                preloader.classList.remove('show', 'd-block');
                preloader.classList.add('d-none');
            }
            ajaxStopFired = true;
        };
        /**
         * Get ajax laoded and fire when done, the native way.
         */
        const useXMLHttpRequestTracking = () =>{
            const originalOpen = XMLHttpRequest.prototype.open;
            // Check if XMLHttpRequest.prototype.open is native or already overridden
            let isNative = true;
            try {
                isNative = originalOpen.toString().includes('[native code]');
            } catch (e) {
                Log.log('unable to determine if native');
                return false;
            }
            if (!isNative) {
                Log.log('Theme already overrides XMLHttpRequest. Skipping custom tracking.');
                return false;
            }
            Log.log('useXMLHttpRequestTracking');
            XMLHttpRequest.prototype.open = function(...args) {
                activeRequests++;
                ajaxStopFired = false;
                this.addEventListener('readystatechange', function() {
                    // Log.log(this.readyState);
                    // Log.log(this.readyState === 4);
                    if (this.readyState === 4) {
                        // Log.log('readystatechange', activeRequests, ajaxStopFired);
                        activeRequests--;
                        if (activeRequests === 0 && !ajaxStopFired) {
                            ajaxStopFired = true;
                            onAjaxStop();
                        }
                    }
                }, false);

                originalOpen.apply(this, args);
            };

            Log.log('Using custom XMLHttpRequest tracking.');
            return true;
        };

        /**
         * Get ajax laoded and fire when done, JQuery way.
         */
        const useJQueryTracking = ()=> {
            $(document).ajaxStart(function() {
                activeRequests++;
                ajaxStopFired = false;
                Log.log('ajaxStart', activeRequests, ajaxStopFired);
            });
            $(document).ajaxStop(function() {
                activeRequests = 0;
                if (!ajaxStopFired) {
                    ajaxStopFired = true;
                    Log.log('ajaxStop', activeRequests, ajaxStopFired);
                    onAjaxStop();
                }
            });
            Log.log('Using jQuery AJAX tracking.');
        };

        // Try XMLHttpRequest tracking first.
        if (!useXMLHttpRequestTracking()) {
            useJQueryTracking();
        }
        // Window.addEventListener('load', Main.init(cfg));
        window.addEventListener('load',
            launch(cfg)
        );
    };
    return {
        init: init
    };
});
