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
 *  description here.
 *
 * @module     'local_deepler'; // Full name of the plugin (used for diagnostics)./glossary
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['./local/selectors', './local/api', './local/customevents', 'core/modal', 'core/log'],
    function(Selectors, Api, Events, Modal, Log) {
    let version = '';
    const initCode = (cfg)=> {
        version = cfg;
        window.console.log(version);
        const fileInput = document.getElementById('fileElem');
        const fileNameDisplay = document.getElementById('filename-display');

        fileInput.addEventListener('change', function() {
            if (fileInput.files.length > 0) {
                fileNameDisplay.textContent = `Selected file: ${fileInput.files[0].name}`;
            } else {
                fileNameDisplay.textContent = '';
            }
        });
    };
    const registerEventListeners = ()=>{
        const allVisibilitySelect = document.querySelectorAll(Selectors.glossary.visibilityDropdown);
        allVisibilitySelect.forEach((e)=>{
        e.addEventListener('change',
                (evt)=>{
                    Log.info(evt.target.dataset.glossary, evt.target.value);
                    Api.updateGlossariesVisibility(evt.target.dataset.glossary, evt.target.value);
                }
            );
        });
        const allGlossarriesEntry = document.querySelectorAll(Selectors.glossary.entriesviewer);
        Log.info(allGlossarriesEntry);
        allGlossarriesEntry.forEach((e)=>{
            e.addEventListener('click',
                (e)=>{
                if (e.target.dataset.length !== 0 && e.target.dataset.glossary !== undefined) {
                    Log.info(e.target.dataset.glossary);
                    Api.getGlossariesEntries(
                        e.target.dataset.glossary,
                        version
                    );
                } else if (e.target.parentNode.dataset && e.target.parentNode.dataset.glossary) {
                    Log.info(e.target.parentNode.dataset.glossary);
                    Api.getGlossariesEntries(
                        e.target.parentNode.dataset.glossary,
                        version
                    );
                } else {
                  Log.error('Cannot not find glossary ID');
                }
            }
            );
        });
        Events.on(Api.GLOSSARY_ENTRIES_SUCCESS, showEntriesModal);
        Events.on(Api.GLOSSARY_ENTRIES_FAILED, (e)=>{
         Log.error(Api.GLOSSARY_ENTRIES_FAILED); Log.error(e);
        });
    };
    const showEntriesModal = (ajaxResponse)=>{
        const glossaryid = ajaxResponse.glossaryid;
        const entries = JSON.parse(ajaxResponse.entries);
        const status = ajaxResponse.status;
        const message = ajaxResponse.message;
        if (status === 'success') {
            const table = document.createElement('table');
            table.className = 'generaltable';
            // Create the header.
            const thead = document.createElement('thead');
            thead.innerHTML = `<tr><th>${ajaxResponse.source.toUpperCase()}</th><th>${ajaxResponse.target.toUpperCase()}</th></tr>`;
            table.appendChild(thead);

            const tbody = document.createElement('tbody');

            Object.entries(entries).forEach(([key, value]) => {
                const row = document.createElement('tr');
                row.innerHTML = `<td>${key}</td><td>${value}</td>`;
                tbody.appendChild(row);
            });

            table.appendChild(tbody);

            Modal.create({
                title: 'Entries',
                body: table,
                type: 'default',
                show: true,
                removeOnClose: true,
            });
        } else {
            Modal.create({
                title: `Error fetching entries for<br/><em>${glossaryid}</em>`,
                body: message,
                type: 'default',
                show: true,
                removeOnClose: true,
            });
        }
    };

    return {
        init: function(cfg) {
            if (document.readyState !== 'loading') {
                // DOM is already ready.
                initCode(cfg);
                registerEventListeners();
            } else {
                // Wait for DOMContentLoaded.
                document.addEventListener('DOMContentLoaded', initCode);
            }
        }
    };
});
