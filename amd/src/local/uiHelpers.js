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
 * @module     'local_deepler'; // Full name of the plugin (used for diagnostics)./local/uiHelpers
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/modal', 'utils', 'selectors'], (Modal, Utils, Selectors)=>{
    let config = {};
    let saveAllModal = {};
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
            thead.innerHTML = `<tr><th>${ajaxResponse.source.toUpperCase()}</th>
                <th>${ajaxResponse.target.toUpperCase()}</th></tr>`;
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
    /**
     * Get the translation row status icon.
     *
     * @param {string} key
     * @returns {*}
     */
    const getIconStatus = (key)=> {
        return Utils.domQuery(Selectors.actions.validatorBtn, key).getAttribute('data-status');
    };
    /**
     * Opens a modal infobox to warn user trunks of fields are saving.
     * @param {object} messageObject
     * @returns {Promise<void>}
     */
    const launchModal = async(messageObject) => {
        saveAllModal = await Modal.create(messageObject);
        await saveAllModal.show();
    };
    const showModal = (title, body, type = 'default') => {
        Modal.create({
            title: title,
            body: body,
            type: type,
            show: true,
            removeOnClose: true,
        });
    };
    const hideModal = ()=>{
        if (saveAllModal !== null && saveAllModal.isVisible) {
            saveAllModal.hide();
        }
    };
    const backToBase = () => {
        const offsetTop = Utils.domQuery(Selectors.config.langstrings).offsetTop;
        window.scrollTo({top: offsetTop - 5, behavior: 'smooth'});
    };
    const init = (cfg) => {
        config = cfg;
    };
    return {
        backToBase: backToBase,
        hideModal: hideModal,
        launchModal: launchModal,
        showModal: showModal,
        getIconStatus: getIconStatus,
        showEntriesModal: showEntriesModal
    };
});
