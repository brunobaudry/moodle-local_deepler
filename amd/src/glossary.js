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
 * @module     local_deepler/deepler
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['./local/selectors', './local/api', 'core/modal', 'core/log'],
    function(Selectors, Api, Modal, Log) {
    let version = '';
    const initCode = (cfg) => {
        version = cfg;
        const fileInput = document.getElementById('local_deepler/fileElem');
        const fileNameDisplay = document.getElementById('local_deepler/filename-display');
        const createGloBtn = document.getElementById('local_deepler/createglossary');

        fileInput.addEventListener('change', function() {
            Log.info("change");
            if (fileInput.files.length > 0) {
                fileNameDisplay.classList.remove('invisible');
                createGloBtn.classList.remove('invisible');
            } else {
                fileNameDisplay.classList.add('invisible');
                createGloBtn.classList.add('invisible');
            }
        });
    };
    const registerEventListeners = () => {
        const allVisibilitySelect = document.querySelectorAll(Selectors.glossary.visibilityDropdown);
        allVisibilitySelect.forEach((e) => {
            e.addEventListener('change',
                (evt) => {
                    Log.info(evt.target.dataset.glossary, evt.target.value);
                    Api.updateGlossariesVisibility(evt.target.dataset.glossary, evt.target.value)
                        .catch((err) => Log.error('Failed to update glossary visibility', err));
                }
            );
        });
        const allGlossarriesEntry = document.querySelectorAll(Selectors.glossary.entriesviewer);
        Log.info(allGlossarriesEntry);
        allGlossarriesEntry.forEach((e) => {
            e.addEventListener('click',
                async(evt) => {
                    let glossaryId = null;
                    if (evt.target.dataset && evt.target.dataset.glossary) {
                        glossaryId = evt.target.dataset.glossary;
                    } else if (evt.target.parentNode && evt.target.parentNode.dataset && evt.target.parentNode.dataset.glossary) {
                        glossaryId = evt.target.parentNode.dataset.glossary;
                    }
                    if (glossaryId) {
                        Log.info(glossaryId);
                        try {
                            const response = await Api.getGlossariesEntries(glossaryId, version);
                            showEntriesModal(response);
                        } catch (err) {
                            Log.error('Error fetching glossary entries', err);
                        }
                    } else {
                        Log.error('Cannot find glossary ID');
                    }
                }
            );
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
